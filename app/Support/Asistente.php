<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Asistente SIGIP con un modelo local de Ollama.
 *
 * Para que no invente cifras, el modelo no consulta la base: SIGIP le entrega los
 * datos agregados del gimnasio (sin datos personales y según el rol) y el modelo
 * solo redacta la respuesta. Si hace falta una gráfica, el modelo elige cuál de los
 * conjuntos de datos mostrar y SIGIP la dibuja con los valores reales.
 */
class Asistente
{
    public const FORMATOS = ['auto', 'texto', 'grafica'];

    /**
     * Conjuntos de datos que el asistente puede usar y graficar.
     * Los ingresos solo los ve el administrador.
     */
    public static function conjuntos(bool $esAdmin): array
    {
        $conjuntos = [
            'accesos_ultimos_14_dias' => ['titulo' => 'Accesos por día (últimos 14 días)', 'tipo' => 'line', 'serie' => 'Accesos', 'datos' => Indicadores::accesosPorDia(14)],
            'accesos_hoy_por_hora' => ['titulo' => 'Accesos de hoy por hora', 'tipo' => 'bar', 'serie' => 'Accesos', 'datos' => Indicadores::accesosHoyPorHora()],
            'planes_mas_asignados' => ['titulo' => 'Planes más asignados', 'tipo' => 'barh-apilada', 'unidad' => 'suscripciones', 'total' => 'total de suscripciones asignadas', 'datos' => Indicadores::planesMasAsignados(8)],
            'suscripciones_por_estado' => ['titulo' => 'Suscripciones por estado', 'tipo' => 'barh', 'serie' => 'Suscripciones', 'datos' => Indicadores::suscripcionesPorEstado()],
            'implementos_por_categoria' => ['titulo' => 'Implementos por categoría', 'tipo' => 'barh-apilada', 'unidad' => 'implementos', 'total' => 'total de implementos', 'datos' => Indicadores::implementosPorCategoria()],
            'usuarios_activos_por_rol' => ['titulo' => 'Usuarios activos por rol', 'tipo' => 'barh', 'serie' => 'Usuarios', 'datos' => Indicadores::usuariosPorRol()],
        ];

        if ($esAdmin) {
            $conjuntos['ingresos_ultimos_6_meses'] = ['titulo' => 'Ingresos verificados por mes (pesos)', 'tipo' => 'bar', 'serie' => 'Ingresos', 'moneda' => true, 'datos' => Indicadores::ingresosPorMes(6)];
        }

        return $conjuntos;
    }

    /**
     * Responde una pregunta. $historial: [['rol' => 'usuario'|'asistente', 'texto' => '...'], ...]
     *
     * @return array{texto: string, grafica: ?array}
     */
    public static function responder(string $pregunta, array $historial, string $formato, bool $esAdmin): array
    {
        // Solo los conjuntos relacionados con la pregunta (y la conversación reciente): el modelo lee
        // menos texto, responde más rápido y se confunde menos.
        $contexto = $pregunta . ' ' . collect(array_slice($historial, -2))->pluck('texto')->implode(' ');
        $conjuntos = self::relevantes(self::conjuntos($esAdmin), $contexto);
        $resumen = Indicadores::resumen($esAdmin);

        $mensajes = [['role' => 'system', 'content' => self::instrucciones($conjuntos, $resumen, $formato, $esAdmin)]];

        foreach (array_slice($historial, -6) as $turno) {
            $mensajes[] = [
                'role' => ($turno['rol'] ?? '') === 'asistente' ? 'assistant' : 'user',
                'content' => mb_substr((string) ($turno['texto'] ?? ''), 0, 1000),
            ];
        }

        $mensajes[] = ['role' => 'user', 'content' => $pregunta];

        $contenido = self::llamarModelo($mensajes);
        $json = json_decode($contenido, true);

        $texto = trim((string) ($json['respuesta'] ?? ''));
        if ($texto === '') {
            // El modelo no devolvió el JSON esperado: se usa el texto tal cual.
            $texto = trim(strip_tags($contenido)) ?: 'No pude generar una respuesta. Intenta preguntar de otra forma.';
        }

        $clave = is_string($json['grafica'] ?? null) ? $json['grafica'] : null;
        $grafica = null;

        if ($formato !== 'texto' && $clave && isset($conjuntos[$clave])) {
            $grafica = ['id' => 'asistente-' . $clave] + $conjuntos[$clave];
        }

        return ['texto' => mb_substr($texto, 0, 3000), 'grafica' => $grafica];
    }

    /**
     * ¿Ollama está en ejecución y tiene el modelo configurado?
     *
     * @return array{disponible: bool, modelo: string, motivo: ?string}
     */
    public static function estado(): array
    {
        $modelo = config('services.ollama.modelo');

        try {
            $respuesta = Http::timeout(3)->get(rtrim(config('services.ollama.url'), '/') . '/api/tags');
        } catch (ConnectionException) {
            return ['disponible' => false, 'modelo' => $modelo, 'motivo' => 'Ollama no está en ejecución en este equipo.'];
        }

        $instalados = collect($respuesta->json('models', []))->pluck('name');
        $tiene = $instalados->contains(fn ($n) => $n === $modelo || $n === "{$modelo}:latest" || str_starts_with((string) $n, $modelo . ':') && !str_contains($modelo, ':'));

        return [
            'disponible' => $respuesta->ok() && $tiene,
            'modelo' => $modelo,
            'motivo' => $tiene ? null : "El modelo {$modelo} no está descargado (ollama pull {$modelo}).",
        ];
    }

    private static function llamarModelo(array $mensajes): string
    {
        // Si la tarjeta de video falló hace poco (o está desactivada), se usa solo el procesador.
        $soloCpu = !config('services.ollama.gpu') || Cache::get('asistente.solo_cpu', false);

        $respuesta = self::pedir($mensajes, $soloCpu);

        // Error del motor de la GPU (controlador no compatible, memoria insuficiente…): se reintenta en CPU
        // y se recuerda durante una hora para no repetir el intento fallido en cada pregunta.
        if (!$respuesta->ok() && !$soloCpu) {
            report(new RuntimeException('Ollama falló con GPU; se reintenta con CPU: ' . $respuesta->json('error')));
            Cache::put('asistente.solo_cpu', true, now()->addHour());
            $respuesta = self::pedir($mensajes, true);
        }

        if (!$respuesta->ok()) {
            report(new RuntimeException('Ollama respondió con error: ' . ($respuesta->json('error') ?? $respuesta->status())));
            throw new RuntimeException('El asistente tuvo un problema al generar la respuesta. Inténtalo de nuevo en un momento.');
        }

        return (string) $respuesta->json('message.content', '');
    }

    private static function pedir(array $mensajes, bool $soloCpu)
    {
        $opciones = ['temperature' => 0.2, 'num_ctx' => 6144];

        if ($soloCpu) {
            $opciones['num_gpu'] = 0;
        }

        try {
            return Http::timeout(config('services.ollama.tiempo'))
                ->post(rtrim(config('services.ollama.url'), '/') . '/api/chat', [
                    'model' => config('services.ollama.modelo'),
                    'messages' => $mensajes,
                    'stream' => false,
                    'format' => 'json',
                    'keep_alive' => '15m',
                    'options' => $opciones,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(str_contains($e->getMessage(), 'timed out')
                ? 'El asistente tardó demasiado en responder. Intenta con una pregunta más corta.'
                : 'El asistente no está disponible: Ollama no está en ejecución.', 0, $e);
        }
    }

    /** Palabras que relacionan una pregunta con cada conjunto de datos. */
    private const PALABRAS = [
        'accesos_ultimos_14_dias' => ['acceso', 'ingres', 'entrad', 'asisten', 'visit', 'semana', 'dias', 'días', 'diario', 'afluencia', 'gente', 'personas'],
        'accesos_hoy_por_hora' => ['hora', 'hoy', 'mañana', 'tarde', 'noche', 'pico', 'concurr', 'ahora', 'dentro'],
        'planes_mas_asignados' => ['plan', 'asign', 'popular', 'elegid', 'vendid', 'tiquetera'],
        'suscripciones_por_estado' => ['suscrip', 'vencid', 'activa', 'por activar', 'pendiente', 'membres'],
        'implementos_por_categoria' => ['implement', 'inventario', 'prest', 'balon', 'balón', 'equipo', 'disponib', 'categor'],
        'usuarios_activos_por_rol' => ['usuario', 'rol', 'estudiant', 'docente', 'egresad', 'empleado', 'familiar', 'comunidad'],
        'ingresos_ultimos_6_meses' => ['ingreso', 'recaud', 'dinero', 'pago', 'pesos', 'plata', 'factur', 'gan', 'mes', 'semestre', 'venta'],
    ];

    /** Filtra los conjuntos por palabras clave; si ninguno coincide, se envían todos. */
    private static function relevantes(array $conjuntos, string $texto): array
    {
        $texto = mb_strtolower($texto);
        $elegidos = array_filter($conjuntos, function ($clave) use ($texto) {
            foreach (self::PALABRAS[$clave] ?? [] as $palabra) {
                if (str_contains($texto, $palabra)) {
                    return true;
                }
            }

            return false;
        }, ARRAY_FILTER_USE_KEY);

        return $elegidos ?: $conjuntos;
    }

    /** Mayor, menor y suma de un conjunto, calculados por SIGIP. */
    private static function hechos(array $conjunto): array
    {
        $etiquetas = $conjunto['datos']['etiquetas'] ?? [];
        $series = $conjunto['datos']['series'] ?? [['valores' => $conjunto['datos']['valores'] ?? []]];

        if ($etiquetas === []) {
            return ['nota' => 'Sin datos registrados.'];
        }

        $totales = [];
        foreach ($etiquetas as $i => $etiqueta) {
            $totales[$etiqueta] = array_sum(array_map(fn ($s) => $s['valores'][$i] ?? 0, $series));
        }

        $maximo = max($totales);
        $minimo = min($totales);
        $unidad = $conjunto['total'] ?? ($conjunto['serie'] ?? 'valor');

        return [
            'mayor' => implode(', ', array_keys($totales, $maximo)) . " ({$maximo})",
            'menor' => implode(', ', array_keys($totales, $minimo)) . " ({$minimo})",
            'suma' => array_sum($totales),
            'medida' => mb_strtolower($unidad),
        ];
    }

    /**
     * Convierte un conjunto en filas legibles para el modelo, por ejemplo
     * [{"etiqueta": "Plan A", "Activas": 1, "Vencidas o por activar": 1, "total": 2}].
     */
    private static function filas(array $conjunto): array
    {
        $etiquetas = $conjunto['datos']['etiquetas'] ?? [];
        $series = $conjunto['datos']['series'] ?? [['nombre' => $conjunto['serie'] ?? 'valor', 'valores' => $conjunto['datos']['valores'] ?? []]];
        $filas = [];

        foreach ($etiquetas as $i => $etiqueta) {
            $fila = ['etiqueta' => $etiqueta];
            $total = 0;

            foreach ($series as $serie) {
                $valor = $serie['valores'][$i] ?? 0;
                // Nombre explícito de la columna ("suscripciones activas") para que el modelo no confunda las cifras.
                $nombre = mb_strtolower($serie['nombre']);
                $fila[isset($conjunto['unidad']) ? "{$conjunto['unidad']} {$nombre}" : $nombre] = $valor;
                $total += $valor;
            }

            if (count($series) > 1) {
                $fila[$conjunto['total'] ?? 'total'] = $total;
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    /** Nombres en lenguaje natural de las cifras del resumen. */
    private const NOMBRES_RESUMEN = [
        'personas_dentro_ahora' => 'Personas dentro del gimnasio en este momento (ingresaron y no han salido)',
        'accesos_hoy' => 'Accesos (entradas de personas al gimnasio) registrados hoy',
        'pagos_por_verificar' => 'Pagos pendientes por verificar',
        'prestamos_activos' => 'Préstamos de implementos activos (sin devolver)',
        'reservas_hoy' => 'Reservas de espacios para hoy',
        'actividades_hoy' => 'Actividades programadas para hoy',
        'usuarios_activos' => 'Usuarios activos',
        'usuarios_registrados' => 'Usuarios registrados en total',
        'ingresos_verificados_mes_actual' => 'Ingresos verificados en el mes actual (pesos)',
    ];

    /**
     * Datos en líneas de texto simples (los modelos pequeños las leen mejor que un JSON
     * y ocupan menos), con los hechos (mayor, menor, suma) ya calculados por SIGIP.
     */
    private static function datosEnTexto(array $conjuntos, array $resumen): string
    {
        $lineas = ['Fecha y hora: ' . now()->locale('es')->translatedFormat('l j \d\e F \d\e Y, h:i a'), '', 'CIFRAS ACTUALES:'];

        foreach ($resumen as $clave => $valor) {
            $texto = is_float($valor) ? '$' . number_format($valor, 0, ',', '.') : $valor;
            $lineas[] = '- ' . (self::NOMBRES_RESUMEN[$clave] ?? $clave) . ': ' . $texto;
        }

        foreach ($conjuntos as $clave => $conjunto) {
            $hechos = self::hechos($conjunto);
            $lineas[] = '';
            $lineas[] = "CONJUNTO {$clave} ({$conjunto['titulo']}):";

            if (isset($hechos['nota'])) {
                $lineas[] = '- ' . $hechos['nota'];
                continue;
            }

            $lineas[] = "- Mayor: {$hechos['mayor']}. Menor: {$hechos['menor']}. Suma de {$hechos['medida']}: {$hechos['suma']}.";

            foreach (self::filas($conjunto) as $fila) {
                $etiqueta = array_shift($fila);
                $lineas[] = "- {$etiqueta}: " . collect($fila)->map(fn ($v, $k) => "{$k} " . (!empty($conjunto['moneda']) ? '$' . number_format((float) $v, 0, ',', '.') : $v))->implode(', ');
            }
        }

        return implode("\n", $lineas);
    }

    private static function instrucciones(array $conjuntos, array $resumen, string $formato, bool $esAdmin): string
    {
        $datos = self::datosEnTexto($conjuntos, $resumen);

        $reglaGrafica = match ($formato) {
            'texto' => 'La persona prefiere respuestas en texto: "grafica" siempre debe ser null.',
            'grafica' => 'La persona prefiere respuestas con gráfica: en "grafica" pon la clave del conjunto que mejor responda la pregunta (solo null si ninguno se relaciona).',
            default => 'Pon una clave en "grafica" solo si la persona pide una gráfica o si una gráfica ayuda claramente a entender la respuesta; si no, null.',
        };

        $rol = $esAdmin ? 'administrador' : 'operador';
        $claves = implode(', ', array_keys($conjuntos));

        return <<<TXT
        Eres el asistente de SIGIP, el sistema del gimnasio universitario de la Institución Universitaria Pascual Bravo (Medellín).
        Hablas con personal del gimnasio con rol {$rol}. Responde siempre en español de Colombia, de forma breve, clara y amable.

        Reglas:
        - Usa ÚNICAMENTE los datos de abajo. No inventes cifras, nombres ni fechas. Si la pregunta no se puede responder con estos datos, dilo y sugiere qué módulo de SIGIP revisar (Usuarios, Planes y suscripciones, Pagos, Control de acceso, Reservas, Espacios, Préstamos, Inventario, Actividades).
        - No tienes datos de personas concretas (nombres, documentos, salud): no los pidas ni los inventes.
        - Los montos están en pesos colombianos; escríbelos con separador de miles (por ejemplo, $1.250.000).
        - Para "el más", "el menos" o totales usa las líneas "Mayor / Menor / Suma" de cada conjunto: ya están calculadas y son exactas.
        - Para preguntas sobre el momento actual (quién está dentro, pendientes de hoy) usa las CIFRAS ACTUALES.
        - Puedes resumir, comparar y explicar tendencias de estos datos.
        - {$reglaGrafica}

        Responde SOLO con un objeto JSON con esta forma exacta:
        {"respuesta": "texto para la persona", "grafica": null}
        donde "grafica" es null o una de estas claves: {$claves}.

        DATOS DE SIGIP:
        {$datos}
        TXT;
    }
}
