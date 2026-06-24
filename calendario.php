<?php
require 'conexion.php';

// Mes y año actual o navegado
$mes = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

// Navegar meses
if ($mes < 1)  { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1;  $anio++; }

$prevMes  = $mes - 1; $prevAnio = $anio;
$nextMes  = $mes + 1; $nextAnio = $anio;
if ($prevMes < 1)  { $prevMes = 12; $prevAnio--; }
if ($nextMes > 12) { $nextMes = 1;  $nextAnio++; }

// Citas del mes
$inicio = sprintf('%04d-%02d-01', $anio, $mes);
$fin    = date('Y-m-t', mktime(0,0,0,$mes,1,$anio));
$result = $conn->query("
    SELECT * FROM citas
    WHERE fecha BETWEEN '$inicio' AND '$fin'
    ORDER BY hora ASC
");

// Agrupar por día
$citasPorDia = [];
while ($c = $result->fetch_assoc()) {
    $dia = (int)date('j', strtotime($c['fecha']));
    $citasPorDia[$dia][] = $c;
}

// Info del mes
$primerDia     = date('N', mktime(0,0,0,$mes,1,$anio)); // 1=Lun
$diasEnMes     = date('t', mktime(0,0,0,$mes,1,$anio));
$nombreMes     = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$mes];
$hoy           = date('j');
$mesHoy        = (int)date('n');
$anioHoy       = (int)date('Y');

$colores = [
    'Pendiente'  => '#d97706',
    'Confirmada' => '#2563eb',
    'Completada' => '#7c3aed',
    'Cancelada'  => '#6b7280',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calendario | Agenda</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; color:#1e293b; }
  nav { background:#1e40af; color:white; padding:1rem 2rem; display:flex; align-items:center; gap:2rem; }
  nav h1 { font-size:1.3rem; }
  nav a { color:#bfdbfe; text-decoration:none; }
  nav a:hover { color:white; }

  .container { max-width:1100px; margin:2rem auto; padding:0 1rem; }

  /* ── Navegación del mes ── */
  .cal-nav {
    display:flex; justify-content:space-between; align-items:center;
    background:white; padding:1rem 1.5rem; border-radius:10px;
    margin-bottom:1rem; box-shadow:0 2px 8px rgba(0,0,0,.07);
  }
  .cal-nav h2 { font-size:1.4rem; }
  .btn { padding:.5rem 1.1rem; border:none; border-radius:7px; cursor:pointer; font-size:.9rem; font-weight:600; text-decoration:none; display:inline-block; }
  .btn-primary   { background:#2563eb; color:white; }
  .btn-secondary { background:#e2e8f0; color:#334155; }
  .btn:hover { opacity:.85; }

  /* ── Grid del calendario ── */
  .dias-semana {
    display:grid; grid-template-columns:repeat(7,1fr);
    gap:4px; margin-bottom:4px;
  }
  .dia-semana {
    text-align:center; padding:.5rem; font-size:.8rem;
    font-weight:700; color:#64748b; background:white;
    border-radius:6px;
  }
  .dia-semana.domingo { color:#dc2626; }

  .cal-grid {
    display:grid; grid-template-columns:repeat(7,1fr);
    gap:4px;
  }

  .celda {
    background:white; border-radius:8px; padding:.5rem;
    min-height:110px; border:2px solid transparent;
    transition:border-color .2s, box-shadow .2s;
    cursor:pointer;
  }
  .celda:hover { border-color:#93c5fd; box-shadow:0 4px 12px rgba(0,0,0,.1); }
  .celda.hoy   { border-color:#2563eb; background:#eff6ff; }
  .celda.pasado{ background:#f8fafc; }
  .celda.vacia { background:transparent; border:none; cursor:default; }
  .celda.domingo .num-dia { color:#dc2626; }

  .num-dia {
    font-weight:700; font-size:.95rem; margin-bottom:.3rem;
    display:inline-block;
  }
  .hoy .num-dia {
    background:#2563eb; color:white;
    border-radius:50%; width:26px; height:26px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.85rem;
  }

  .evento {
    font-size:.7rem; padding:2px 6px; border-radius:4px;
    color:white; margin-bottom:2px; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis;
  }
  .mas { font-size:.7rem; color:#94a3b8; margin-top:2px; }

  /* ── Panel de citas del día (modal simple) ── */
  .modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.45); z-index:100;
    justify-content:center; align-items:center;
  }
  .modal-overlay.abierto { display:flex; }
  .modal {
    background:white; border-radius:12px; padding:1.5rem;
    width:90%; max-width:480px; max-height:80vh; overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
  }
  .modal h3 { margin-bottom:1rem; font-size:1.1rem; }
  .modal-cita {
    display:flex; gap:.8rem; align-items:center;
    padding:.6rem .8rem; border-radius:8px; margin-bottom:.5rem;
    background:#f8fafc; border-left:4px solid var(--c);
  }
  .modal-cita .hora { font-weight:700; min-width:45px; color:var(--c); }
  .modal-cita .info { flex:1; }
  .modal-cita .nombre { font-weight:600; font-size:.9rem; }
  .modal-cita .detalle { font-size:.78rem; color:#64748b; }
  .modal-cita .badge { font-size:.7rem; padding:2px 8px; border-radius:10px; color:white; background:var(--c); }
  .modal-footer { margin-top:1rem; text-align:right; }
  .sin-citas { color:#94a3b8; text-align:center; padding:1.5rem; }

  /* ── Leyenda ── */
  .leyenda {
    display:flex; gap:1rem; flex-wrap:wrap;
    background:white; padding:.8rem 1.2rem;
    border-radius:8px; margin-bottom:1rem;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
  }
  .leyenda-item { display:flex; align-items:center; gap:.4rem; font-size:.82rem; }
  .leyenda-dot  { width:12px; height:12px; border-radius:50%; }
</style>
</head>
<body>

<nav>
  <h1>🏥 Agenda de Citas</h1>
  <a href="index.php">📋 Citas</a>
  <a href="calendario.php">🗓️ Calendario</a>
  <a href="nueva.php">➕ Nueva Cita</a>
</nav>

<div class="container">

  <!-- Navegación mes -->
  <div class="cal-nav">
    <a href="calendario.php?mes=<?= $prevMes ?>&anio=<?= $prevAnio ?>" class="btn btn-secondary">◀ Anterior</a>
    <h2>🗓️ <?= $nombreMes ?> <?= $anio ?></h2>
    <div style="display:flex;gap:.5rem">
      <a href="calendario.php" class="btn btn-secondary">Hoy</a>
      <a href="calendario.php?mes=<?= $nextMes ?>&anio=<?= $nextAnio ?>" class="btn btn-primary">Siguiente ▶</a>
    </div>
  </div>

  <!-- Leyenda -->
  <div class="leyenda">
    <?php foreach ($colores as $estado => $color): ?>
      <div class="leyenda-item">
        <div class="leyenda-dot" style="background:<?= $color ?>"></div>
        <span><?= $estado ?></span>
      </div>
    <?php endforeach; ?>
    <div class="leyenda-item">
      <div class="leyenda-dot" style="background:#2563eb;border:2px solid #2563eb"></div>
      <span>Hoy</span>
    </div>
  </div>

  <!-- Encabezados días -->
  <div class="dias-semana">
    <?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $i => $d): ?>
      <div class="dia-semana <?= $i===6 ? 'domingo':'' ?>"><?= $d ?></div>
    <?php endforeach; ?>
  </div>

  <!-- Grid calendario -->
  <div class="cal-grid">

    <!-- Celdas vacías antes del día 1 -->
    <?php for ($i = 1; $i < $primerDia; $i++): ?>
      <div class="celda vacia"></div>
    <?php endfor; ?>

    <!-- Días del mes -->
    <?php for ($dia = 1; $dia <= $diasEnMes; $dia++):
      $fecha       = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
      $diaSemana   = date('N', strtotime($fecha)); // 1=Lun 7=Dom
      $esHoy       = ($dia == $hoy && $mes == $mesHoy && $anio == $anioHoy);
      $esPasado    = (strtotime($fecha) < strtotime(date('Y-m-d')));
      $esDomingo   = ($diaSemana == 7);
      $citas       = $citasPorDia[$dia] ?? [];
      $clases      = 'celda';
      if ($esHoy)    $clases .= ' hoy';
      if ($esPasado && !$esHoy) $clases .= ' pasado';
      if ($esDomingo) $clases .= ' domingo';
    ?>
      <div class="<?= $clases ?>"
           onclick="abrirModal(<?= $dia ?>, '<?= $fecha ?>', <?= count($citas) ?>)">

        <span class="num-dia"><?= $dia ?></span>

        <?php
          $mostrar = array_slice($citas, 0, 3);
          foreach ($mostrar as $c):
            $color = $colores[$c['estado']];
        ?>
          <div class="evento" style="background:<?= $color ?>"
               title="<?= htmlspecialchars($c['nombre']) ?> - <?= $c['estado'] ?>">
            <?= substr($c['hora'],0,5) ?> <?= htmlspecialchars(mb_strimwidth($c['nombre'],0,18,'…')) ?>
          </div>
        <?php endforeach; ?>

        <?php if (count($citas) > 3): ?>
          <div class="mas">+<?= count($citas)-3 ?> más</div>
        <?php endif; ?>

      </div>
    <?php endfor; ?>

  </div><!-- /cal-grid -->

</div><!-- /container -->

<!-- Modal de detalle del día -->
<div class="modal-overlay" id="modalOverlay" onclick="cerrarModal(event)">
  <div class="modal" id="modalContent">
    <h3 id="modalTitulo">Citas del día</h3>
    <div id="modalCuerpo"></div>
    <div class="modal-footer">
      <a id="btnNuevaDia" href="#" class="btn btn-primary" style="margin-right:.5rem">➕ Nueva cita este día</a>
      <button onclick="cerrarModal()" class="btn btn-secondary">Cerrar</button>
    </div>
  </div>
</div>

<script>
// Datos de citas desde PHP
const citasPorDia = <?php
  $salida = [];
  foreach ($citasPorDia as $dia => $citas) {
      $salida[$dia] = array_map(function($c) {
          return [
              'id'     => $c['id'],
              'hora'   => substr($c['hora'],0,5),
              'nombre' => htmlspecialchars($c['nombre'], ENT_QUOTES),
              'motivo' => htmlspecialchars(mb_strimwidth($c['motivo'],0,60,'…'), ENT_QUOTES),
              'estado' => $c['estado'],
              'telefono' => htmlspecialchars($c['telefono'], ENT_QUOTES),
          ];
      }, $citas);
  }
  echo json_encode($salida);
?>;

const colores = {
  'Pendiente' :'#d97706',
  'Confirmada':'#2563eb',
  'Completada':'#7c3aed',
  'Cancelada' :'#6b7280'
};

const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

function abrirModal(dia, fecha, total) {
  const overlay = document.getElementById('modalOverlay');
  const titulo  = document.getElementById('modalTitulo');
  const cuerpo  = document.getElementById('modalCuerpo');
  const btnNueva= document.getElementById('btnNuevaDia');

  titulo.textContent = `Citas del ${dia} de <?= $nombreMes ?> <?= $anio ?>`;
  btnNueva.href = `nueva.php?fecha=${fecha}`;
  cuerpo.innerHTML = '';

  const citas = citasPorDia[dia] || [];
  if (citas.length === 0) {
    cuerpo.innerHTML = '<div class="sin-citas">Sin citas para este día.<br>¡Puedes agregar una!</div>';
  } else {
    citas.forEach(c => {
      const color = colores[c.estado] || '#6b7280';
      cuerpo.innerHTML += `
        <div class="modal-cita" style="--c:${color}">
          <span class="hora">${c.hora}</span>
          <div class="info">
            <div class="nombre">${c.nombre}</div>
            <div class="detalle">📋 ${c.motivo}</div>
            <div class="detalle">📞 ${c.telefono}</div>
          </div>
          <div>
            <span class="badge">${c.estado}</span><br><br>
            <a href="editar.php?id=${c.id}" style="font-size:.75rem;color:#2563eb;">✏️ Editar</a>
          </div>
        </div>`;
    });
  }

  overlay.classList.add('abierto');
}

function cerrarModal(e) {
  if (!e || e.target === document.getElementById('modalOverlay')) {
    document.getElementById('modalOverlay').classList.remove('abierto');
  }
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') cerrarModal();
});
</script>

</body>
</html>
