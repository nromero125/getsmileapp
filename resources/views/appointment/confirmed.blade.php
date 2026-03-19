<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $success ? 'Cita confirmada' : 'Enlace inválido' }} — GetSmile</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      background: #0F1F3D;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      display: flex; align-items: center; justify-content: center; padding: 24px;
    }
    .card {
      background: #fff; border-radius: 24px; padding: 48px 40px;
      max-width: 480px; width: 100%; text-align: center;
      box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    }
    .icon {
      width: 72px; height: 72px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 24px;
    }
    .icon-success { background: #E6F7F5; }
    .icon-error   { background: #FEE2E2; }
    h1 { font-size: 26px; font-weight: 700; color: #0F1F3D; margin-bottom: 10px; }
    .subtitle { font-size: 15px; color: #5a6e8c; line-height: 1.6; margin-bottom: 32px; }
    .detail-card {
      background: #F7F9FC; border-radius: 14px; padding: 20px 24px;
      text-align: left; margin-bottom: 32px;
    }
    .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #E8EDF3; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row span:first-child { color: #8fa3bf; font-weight: 500; }
    .detail-row span:last-child  { color: #0F1F3D; font-weight: 600; }
    .badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: #E6F7F5; color: #00BFA6;
      font-size: 13px; font-weight: 700; padding: 6px 14px;
      border-radius: 999px; margin-bottom: 32px;
    }
    .logo { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; }
    .logo-icon { width: 28px; height: 28px; background: #00BFA6; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .logo-name { font-size: 15px; font-weight: 700; color: #8fa3bf; }
  </style>
</head>
<body>
  <div class="card">
    @if($success)
      <div class="icon icon-success">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#00BFA6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6L9 17l-5-5"/>
        </svg>
      </div>
      <h1>¡Cita confirmada!</h1>
      <p class="subtitle">Tu asistencia ha sido registrada. Te esperamos en la clínica.</p>

      <div class="badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        Confirmada
      </div>

      <div class="detail-card">
        <div class="detail-row">
          <span>Clínica</span>
          <span>{{ $appointment->clinic->name }}</span>
        </div>
        <div class="detail-row">
          <span>Fecha</span>
          <span>{{ $appointment->appointment_date->locale('es')->isoFormat('ddd D MMM, YYYY') }}</span>
        </div>
        <div class="detail-row">
          <span>Hora</span>
          <span>{{ $appointment->appointment_date->format('H:i') }}</span>
        </div>
        <div class="detail-row">
          <span>Dentista</span>
          <span>{{ $appointment->dentist->name }}</span>
        </div>
        @if($appointment->reason)
        <div class="detail-row">
          <span>Motivo</span>
          <span>{{ $appointment->reason }}</span>
        </div>
        @endif
      </div>

    @else
      <div class="icon icon-error">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>
      <h1>Enlace inválido</h1>
      <p class="subtitle">Este enlace de confirmación ya fue usado, expiró o no es válido.<br/>Comunícate directamente con la clínica si necesitas confirmar tu cita.</p>
    @endif

    <div class="logo">
      <div class="logo-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z"/>
        </svg>
      </div>
      <span class="logo-name">GetSmile</span>
    </div>
  </div>
</body>
</html>
