<?php
session_start();
require 'conexion.php';

// Token CSRF para proteger la acción de eliminar
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ── Filtros ───────────────────────────────────────────────────────
$filtro_nombre = trim($_GET['nombre'] ?? '');
$filtro_estado = $_GET['estado'] ?? '';
$filtro_desde  = $_GET['desde'] ?? '';
$filtro_hasta  = $_GET['hasta'] ?? '';

$where = "WHERE 1=1";
if ($filtro_nombre !== '') {
    $n = $conn->real_escape_string($filtro_nombre);
    $where .= " AND nombre LIKE '%$n%'";
}
if ($filtro_estado !== '') {
    $e = $conn->real_escape_string($filtro_estado);
    $where .= " AND estado = '$e'";
}
if ($filtro_desde !== '') {
    $where .= " AND fecha >= '" . $conn->real_escape_string($filtro_desde) . "'";
}
if ($filtro_hasta !== '') {
    $where .= " AND fecha <= '" . $conn->real_escape_string($filtro_hasta) . "'";
}

$result = $conn->query("SELECT * FROM citas $where ORDER BY fecha ASC, hora ASC");

// Estadísticas
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(fecha = CURDATE()) AS hoy,
        SUM(estado = 'Pendiente') AS pendientes,
        SUM(estado = 'Confirmada') AS confirmadas
    FROM citas
")->fetch_assoc();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Agenda de Citas</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; color:#1e293b; }

  nav {
    background:#1e40af; color:white; padding:1rem 2rem;
    display:flex; align-items:center; gap:2rem;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
  }
  nav h1 { font-size:1.3rem; }
  nav a  { color:#bfdbfe; text-decoration:none; font-size:.95rem; }
  nav a:hover { color:white; }

  .container { max-width:1200px; margin:2rem auto; padding:0 1rem; }

  /* Stats */
  .stats { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
  .stat-card {
    background:white; border-radius:10px; padding:1.2rem 1.8rem;
    flex:1; min-width:140px; box-shadow:0 2px 8px rgba(0,0,0,.07);
    border-left:5px solid var(--c);
  }
  .stat-card .num { font-size:2.2rem; font-weight:700; color:var(--c); }
  .stat-card .lbl { font-size:.85rem; color:#64748b; }

  /* Filtros */
  .filtros {
    background:white; padding:1rem 1.2rem; border-radius:10px;
    margin-bottom:1.2rem; display:flex; gap:.8rem; flex-wrap:wrap;
    align-items:flex-end; box-shadow:0 1px 4px rgba(0,0,0,.07);
  }
  .filtros label { display:flex; flex-direction:column; gap:3px; font-size:.85rem; color:#475569; }
  .filtros input, .filtros select {
    padding:.4rem .7rem; border:1px solid #cbd5e1;
    border-radius:6px; font-size:.9rem; font-family:inherit;
  }

  .btn { padding:.45rem 1rem; border:none; border-radius:6px; cursor:pointer; font-size:.9rem; font-weight:600; text-decoration:none; display:inline-block; }
  .btn-primary   { background:#2563eb; color:white; }
  .btn-secondary { background:#e2e8f0; color:#334155; }
  .btn-success   { background:#059669; color:white; }
  .btn-danger    { background:#dc2626; color:white; }
  .btn-warning   { background:#d97706; color:white; }
  .btn-sm        { padding:.28rem .6rem; font-size:.8rem; }
  .btn:hover     { opacity:.87; }

  /* Tabla */
  .card { background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.07); overflow:hidden; }
  .card-header {
    padding:1rem 1.5rem; display:flex; justify-content:space-between;
    align-items:center; border-bottom:1px solid #e2e8f0;
  }
  .card-header h2 { font-size:1.1rem; }
  table { width:100%; border-collapse:collapse; }
  th { background:#f8fafc; padding:.75rem 1rem; text-align:left; font-size:.83rem; color:#475569; border-bottom:2px solid #e2e8f0; }
  td { padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; font-size:.9rem; vertical-align:middle; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:#f8fafc; }

  .badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.75rem; font-weight:600; color:white; }
  .badge-Pendiente  { background:#d97706; }
  .badge-Confirmada { background:#2563eb; }
  .badge-Completada { background:#7c3aed; }
  .badge-Cancelada  { background:#6b7280; }

  .alert { padding:.8rem 1.2rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem; }
  .alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }

  .acciones { display:flex; gap:.4rem; }
  .vacio { text-align:center; padding:3rem; color:#94a3b8; }
</style>
</head>
<body>

<nav>
  <h1>🏥 Agenda de Citas</h1>
  <a href="index.php">📋 Citas</a>
  <a href="calendario.php">🗓️ Calendario</a>
  <a href="nueva.php">➕ Nueva Cita</a>
  <a href="estadisticas.php">📊 Estadísticas</a>
</nav>

<div class="container">

  <?php if ($msg === 'creada'):  echo '<div class="alert alert-success">✅ Cita creada correctamente.</div>'; endif; ?>
  <?php if ($msg === 'editada'): echo '<div class="alert alert-success">✅ Cita actualizada.</div>'; endif; ?>
  <?php if ($msg === 'borrada'): echo '<div class="alert alert-success">🗑️ Cita eliminada.</div>'; endif; ?>
  <?php if ($msg === 'error_csrf'): echo '<div class="alert" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">⚠️ No se pudo completar la acción (token inválido o expirado).</div>'; endif; ?>

  <!-- Estadísticas -->
  <div class="stats">
    <div class="stat-card" style="--c:#2563eb">
      <div class="num"><?= $stats['total'] ?></div>
      <div class="lbl">Total citas</div>
    </div>
    <div class="stat-card" style="--c:#059669">
      <div class="num"><?= $stats['hoy'] ?></div>
      <div class="lbl">Hoy</div>
    </div>
    <div class="stat-card" style="--c:#d97706">
      <div class="num"><?= $stats['pendientes'] ?></div>
      <div class="lbl">Pendientes</div>
    </div>
    <div class="stat-card" style="--c:#7c3aed">
      <div class="num"><?= $stats['confirmadas'] ?></div>
      <div class="lbl">Confirmadas</div>
    </div>
  </div>

  <!-- Filtros -->
  <form method="GET" action="index.php">
    <div class="filtros">
      <label>Buscar paciente
        <input type="text" name="nombre" value="<?= htmlspecialchars($filtro_nombre) ?>" placeholder="Nombre...">
      </label>
      <label>Estado
        <select name="estado">
          <option value="">Todos</option>
          <?php foreach (['Pendiente','Confirmada','Completada','Cancelada'] as $e): ?>
            <option value="<?= $e ?>" <?= $filtro_estado===$e?'selected':'' ?>><?= $e ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Desde
        <input type="date" name="desde" value="<?= $filtro_desde ?>">
      </label>
      <label>Hasta
        <input type="date" name="hasta" value="<?= $filtro_hasta ?>">
      </label>
      <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
      <a href="index.php" class="btn btn-secondary">✖ Limpiar</a>
    </div>
  </form>

  <!-- Tabla -->
  <div class="card">
    <div class="card-header">
      <h2>📋 Lista de Citas (<?= $result->num_rows ?>)</h2>
      <a href="nueva.php" class="btn btn-success">➕ Nueva Cita</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
      <div class="vacio">😔 No se encontraron citas.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Paciente</th>
          <th>Teléfono</th>
          <th>Motivo</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($c = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
          <td><?= substr($c['hora'],0,5) ?></td>
          <td>
            <strong><?= htmlspecialchars($c['nombre']) ?></strong><br>
            <small style="color:#64748b"><?= htmlspecialchars($c['email']) ?></small>
          </td>
          <td><?= htmlspecialchars($c['telefono']) ?></td>
          <td><?= htmlspecialchars(mb_strimwidth($c['motivo'],0,40,'…')) ?></td>
          <td><span class="badge badge-<?= $c['estado'] ?>"><?= $c['estado'] ?></span></td>
          <td>
            <div class="acciones">
              <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
              <a href="eliminar.php?id=<?= $c['id'] ?>&token=<?= $csrf_token ?>"
                 onclick="return confirm('¿Eliminar la cita de <?= addslashes(htmlspecialchars($c['nombre'])) ?>?')"
                 class="btn btn-danger btn-sm">🗑️</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
