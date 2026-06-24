<?php
require 'conexion.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Cargar cita existente
$cita = $conn->query("SELECT * FROM citas WHERE id = $id")->fetch_assoc();
if (!$cita) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha    = $_POST['fecha'] ?? '';
    $hora     = $_POST['hora'] ?? '';
    $motivo   = trim($_POST['motivo'] ?? '');
    $estado   = $_POST['estado'] ?? 'Pendiente';
    $notas    = trim($_POST['notas'] ?? '');

    // ── Validaciones ──────────────────────────────────────────────
    if (!$nombre || !$email || !$telefono || !$fecha || !$hora || !$motivo) {
        $error = 'Por favor completa todos los campos obligatorios.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';

    } elseif (date('N', strtotime($fecha)) == 7) {
        $error = 'No se atiende los domingos.';

    } else {
        $diaSemana = date('N', strtotime($fecha));
        $horaN = (int)str_replace(':', '', $hora);

        if ($diaSemana == 6 && ($horaN < 900 || $horaN > 1230)) {
            $error = 'Los sábados se atiende de 09:00 a 13:00.';
        } elseif ($diaSemana != 6 && ($horaN < 800 || $horaN > 1730)) {
            $error = 'De lunes a viernes se atiende de 08:00 a 18:00.';
        }

        if (!$error) {
            // Validar conflicto excluyendo la cita actual
            $f = $conn->real_escape_string($fecha);
            $h = $conn->real_escape_string($hora);
            $chk = $conn->query("
                SELECT id, nombre, hora FROM citas
                WHERE fecha = '$f'
                  AND id != $id
                  AND estado != 'Cancelada'
                  AND ABS(TIMESTAMPDIFF(MINUTE, CONCAT('$f',' ','$h'), CONCAT(fecha,' ',hora))) < 30
            ");

            if ($chk->num_rows > 0) {
                $c2 = $chk->fetch_assoc();
                $error = "Conflicto: ya hay una cita a las " . substr($c2['hora'],0,5) .
                         " con " . htmlspecialchars($c2['nombre']) .
                         ". Elige un horario con al menos 30 min de diferencia.";
            } else {
                $n  = $conn->real_escape_string($nombre);
                $em = $conn->real_escape_string($email);
                $t  = $conn->real_escape_string($telefono);
                $m  = $conn->real_escape_string($motivo);
                $es = $conn->real_escape_string($estado);
                $no = $conn->real_escape_string($notas);

                $conn->query("
                    UPDATE citas SET
                        nombre='$n', email='$em', telefono='$t',
                        fecha='$f', hora='$h', motivo='$m',
                        estado='$es', notas='$no'
                    WHERE id=$id
                ");
                header('Location: index.php?msg=editada');
                exit;
            }
        }
    }

    // Si hay error, rellenar con lo enviado
    $cita = array_merge($cita, $_POST);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editar Cita | Agenda</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; color:#1e293b; }
  nav { background:#1e40af; color:white; padding:1rem 2rem; display:flex; align-items:center; gap:2rem; }
  nav h1 { font-size:1.3rem; }
  nav a { color:#bfdbfe; text-decoration:none; }
  nav a:hover { color:white; }
  .container { max-width:650px; margin:2rem auto; padding:0 1rem; }
  .card { background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .card-header { padding:1.2rem 1.5rem; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
  .card-header h2 { font-size:1.15rem; }
  .card-body { padding:1.5rem; }
  .form-group { margin-bottom:1.1rem; }
  label { display:block; font-size:.875rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
  input, select, textarea { width:100%; padding:.55rem .85rem; border:1px solid #d1d5db; border-radius:7px; font-size:.95rem; font-family:inherit; }
  input:focus, select:focus, textarea:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
  textarea { resize:vertical; min-height:80px; }
  .row { display:flex; gap:1rem; }
  .row .form-group { flex:1; }
  .btn { padding:.6rem 1.4rem; border:none; border-radius:7px; cursor:pointer; font-size:.95rem; font-weight:600; }
  .btn-primary  { background:#2563eb; color:white; }
  .btn-secondary{ background:#e5e7eb; color:#374151; }
  .btn-danger   { background:#dc2626; color:white; }
  .btn:hover { opacity:.87; }
  .alert-danger { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:.8rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem; }
  .required { color:#dc2626; }
  .hint { font-size:.78rem; color:#6b7280; margin-top:.2rem; }
  .footer-btns { display:flex; gap:.8rem; justify-content:flex-end; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #f1f5f9; }
  .id-badge { background:#f1f5f9; padding:.3rem .8rem; border-radius:20px; font-size:.85rem; color:#64748b; }
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
  <div class="card">
    <div class="card-header">
      <h2>✏️ Editar Cita</h2>
      <span class="id-badge">ID #<?= $id ?></span>
    </div>
    <div class="card-body">

      <?php if ($error): ?>
        <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="editar.php?id=<?= $id ?>">

        <div class="form-group">
          <label>Nombre del paciente <span class="required">*</span></label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($cita['nombre']) ?>" required>
        </div>

        <div class="row">
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" value="<?= htmlspecialchars($cita['email']) ?>" required>
          </div>
          <div class="form-group">
            <label>Teléfono <span class="required">*</span></label>
            <input type="tel" name="telefono" value="<?= htmlspecialchars($cita['telefono']) ?>" required>
          </div>
        </div>

        <div class="row">
          <div class="form-group">
            <label>Fecha <span class="required">*</span></label>
            <input type="date" name="fecha" value="<?= $cita['fecha'] ?>" required>
            <div class="hint">Lun–Vie 08:00–18:00 · Sáb 09:00–13:00 · Dom cerrado</div>
          </div>
          <div class="form-group">
            <label>Hora <span class="required">*</span></label>
            <input type="time" name="hora" value="<?= substr($cita['hora'],0,5) ?>" step="1800" required>
          </div>
        </div>

        <div class="form-group">
          <label>Motivo de consulta <span class="required">*</span></label>
          <textarea name="motivo" required><?= htmlspecialchars($cita['motivo']) ?></textarea>
        </div>

        <div class="row">
          <div class="form-group">
            <label>Estado</label>
            <select name="estado">
              <?php foreach (['Pendiente','Confirmada','Completada','Cancelada'] as $e): ?>
                <option value="<?= $e ?>" <?= $cita['estado']===$e ? 'selected':'' ?>><?= $e ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Notas adicionales</label>
          <textarea name="notas"><?= htmlspecialchars($cita['notas'] ?? '') ?></textarea>
        </div>

        <div class="footer-btns">
          <a href="index.php" class="btn btn-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
        </div>

      </form>
    </div>
  </div>
</div>
</body>
</html>
