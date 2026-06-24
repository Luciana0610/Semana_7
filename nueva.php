<?php
require 'conexion.php';

$error = '';
$fecha_default = $_GET['fecha'] ?? date('Y-m-d');

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
        $error = 'Completa todos los campos obligatorios.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no tiene un formato válido.';

    } elseif ($fecha < date('Y-m-d')) {
        $error = 'No se pueden crear citas en fechas pasadas.';

    } elseif (date('N', strtotime($fecha)) == 7) {
        $error = 'No se atiende los domingos. Por favor elige otro día.';

    } else {
        $diaSemana = (int)date('N', strtotime($fecha));
        $horaN = (int)str_replace(':', '', substr($hora, 0, 5));

        if ($diaSemana == 6 && ($horaN < 900 || $horaN > 1230)) {
            $error = 'Los sábados el horario es de 09:00 a 13:00.';
        } elseif ($diaSemana != 6 && ($horaN < 800 || $horaN > 1730)) {
            $error = 'De lunes a viernes el horario es de 08:00 a 18:00.';
        }

        if (!$error) {
            // Validar conflicto ±30 min
            $f  = $conn->real_escape_string($fecha);
            $h  = $conn->real_escape_string($hora);
            $chk = $conn->query("
                SELECT nombre, hora FROM citas
                WHERE fecha = '$f'
                  AND estado != 'Cancelada'
                  AND ABS(TIMESTAMPDIFF(MINUTE,
                      CONCAT('$f',' ','$h'),
                      CONCAT(fecha,' ',hora))) < 30
            ");

            if ($chk->num_rows > 0) {
                $con = $chk->fetch_assoc();
                $error = "Conflicto: ya hay una cita a las " . substr($con['hora'],0,5) .
                         " con " . htmlspecialchars($con['nombre']) .
                         ". Elige un horario con al menos 30 min de diferencia.";
            } else {
                $n  = $conn->real_escape_string($nombre);
                $em = $conn->real_escape_string($email);
                $t  = $conn->real_escape_string($telefono);
                $m  = $conn->real_escape_string($motivo);
                $es = $conn->real_escape_string($estado);
                $no = $conn->real_escape_string($notas);

                $conn->query("INSERT INTO citas (nombre,email,telefono,fecha,hora,motivo,estado,notas)
                              VALUES ('$n','$em','$t','$f','$h','$m','$es','$no')");
                header('Location: index.php?msg=creada');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nueva Cita | Agenda</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:#f1f5f9; color:#1e293b; }
  nav { background:#1e40af; color:white; padding:1rem 2rem; display:flex; align-items:center; gap:2rem; }
  nav h1 { font-size:1.3rem; }
  nav a { color:#bfdbfe; text-decoration:none; }
  nav a:hover { color:white; }
  .container { max-width:640px; margin:2rem auto; padding:0 1rem; }
  .card { background:white; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .card-header { padding:1.2rem 1.5rem; border-bottom:1px solid #e2e8f0; }
  .card-header h2 { font-size:1.15rem; }
  .card-body { padding:1.5rem; }
  .form-group { margin-bottom:1rem; }
  label { display:block; font-size:.875rem; font-weight:600; color:#374151; margin-bottom:.3rem; }
  input, select, textarea {
    width:100%; padding:.55rem .85rem; border:1px solid #d1d5db;
    border-radius:7px; font-size:.95rem; font-family:inherit;
  }
  input:focus, select:focus, textarea:focus {
    outline:none; border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.15);
  }
  textarea { resize:vertical; min-height:80px; }
  .row { display:flex; gap:1rem; }
  .row .form-group { flex:1; }
  .btn { padding:.6rem 1.4rem; border:none; border-radius:7px; cursor:pointer; font-size:.95rem; font-weight:600; text-decoration:none; display:inline-block; }
  .btn-primary   { background:#2563eb; color:white; }
  .btn-secondary { background:#e5e7eb; color:#374151; }
  .btn:hover { opacity:.87; }
  .alert-danger { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:.8rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:.9rem; }
  .required { color:#dc2626; }
  .hint { font-size:.78rem; color:#6b7280; margin-top:.2rem; }
  .footer-btns { display:flex; gap:.8rem; justify-content:flex-end; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #f1f5f9; }
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
    <div class="card-header"><h2>➕ Nueva Cita</h2></div>
    <div class="card-body">

      <?php if ($error): ?>
        <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="nueva.php">

        <div class="form-group">
          <label>Nombre del paciente <span class="required">*</span></label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Ej: María García" required>
        </div>

        <div class="row">
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="correo@ejemplo.com" required>
          </div>
          <div class="form-group">
            <label>Teléfono <span class="required">*</span></label>
            <input type="tel" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" placeholder="3001234567" required>
          </div>
        </div>

        <div class="row">
          <div class="form-group">
            <label>Fecha <span class="required">*</span></label>
            <input type="date" name="fecha" value="<?= $_POST['fecha'] ?? $fecha_default ?>" min="<?= date('Y-m-d') ?>" required>
            <div class="hint">Lun–Vie 08:00–18:00 · Sáb 09:00–13:00 · Dom cerrado</div>
          </div>
          <div class="form-group">
            <label>Hora <span class="required">*</span></label>
            <input type="time" name="hora" value="<?= $_POST['hora'] ?? '09:00' ?>" step="1800" required>
            <div class="hint">Intervalos de 30 minutos</div>
          </div>
        </div>

        <div class="form-group">
          <label>Motivo de consulta <span class="required">*</span></label>
          <textarea name="motivo" placeholder="Describe brevemente el motivo..." required><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Estado</label>
          <select name="estado">
            <?php foreach (['Pendiente','Confirmada','Completada','Cancelada'] as $e): ?>
              <option value="<?= $e ?>" <?= ($_POST['estado'] ?? 'Pendiente')===$e?'selected':'' ?>><?= $e ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Notas adicionales</label>
          <textarea name="notas" placeholder="Alergias, medicamentos, observaciones..."><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
        </div>

        <div class="footer-btns">
          <a href="index.php" class="btn btn-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary">💾 Guardar Cita</button>
        </div>

      </form>
    </div>
  </div>
</div>
</body>
</html>
