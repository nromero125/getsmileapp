<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirma tu cita</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #F0F4F8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a2744; }
    .wrapper { max-width: 560px; margin: 40px auto; }
    .header { background: #0F1F3D; border-radius: 16px 16px 0 0; padding: 32px 40px; display: flex; align-items: center; gap: 12px; }
    .logo-icon { width: 40px; height: 40px; background: #00BFA6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .logo-name { color: #fff; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
    .body { background: #ffffff; padding: 40px; }
    .greeting { font-size: 22px; font-weight: 700; color: #0F1F3D; margin-bottom: 8px; }
    .subtext { font-size: 15px; color: #5a6e8c; margin-bottom: 32px; line-height: 1.6; }
    .detail-card { background: #F7F9FC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 24px; margin-bottom: 32px; }
    .detail-row { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 14px; }
    .detail-row:last-child { margin-bottom: 0; }
    .detail-icon { width: 36px; height: 36px; background: #E6F7F5; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .detail-label { font-size: 11px; font-weight: 600; color: #8fa3bf; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
    .detail-value { font-size: 15px; font-weight: 600; color: #0F1F3D; }
    .cta { text-align: center; margin-bottom: 32px; }
    .cta-btn { display: inline-block; background: #00BFA6; color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: 700; padding: 16px 40px; border-radius: 12px; letter-spacing: 0.2px; }
    .cta-btn:hover { background: #009e8a; }
    .notice { font-size: 13px; color: #8fa3bf; text-align: center; line-height: 1.6; margin-bottom: 8px; }
    .link-fallback { font-size: 12px; color: #b0bec5; word-break: break-all; text-align: center; }
    .footer { background: #0F1F3D; border-radius: 0 0 16px 16px; padding: 20px 40px; text-align: center; }
    .footer p { font-size: 12px; color: #4e6280; line-height: 1.6; }
    .footer a { color: #00BFA6; text-decoration: none; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z"/>
      </svg>
    </div>
    <span class="logo-name">Dentarix</span>
  </div>

  <div class="body">
    <p class="greeting">Hola, {{ $appointment->patient->first_name }} 👋</p>
    <p class="subtext">
      Tienes una cita programada en <strong>{{ $appointment->clinic->name }}</strong>.<br/>
      Por favor confirma tu asistencia haciendo clic en el botón de abajo.
    </p>

    <div class="detail-card">
      <div class="detail-row">
        <div class="detail-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00BFA6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
          <p class="detail-label">Fecha y hora</p>
          <p class="detail-value">{{ $appointment->appointment_date->locale('es')->isoFormat('dddd D [de] MMMM, YYYY [·] HH:mm') }}</p>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00BFA6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <p class="detail-label">Dentista</p>
          <p class="detail-value">{{ $appointment->dentist->name }}</p>
        </div>
      </div>
      @if($appointment->reason)
      <div class="detail-row">
        <div class="detail-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00BFA6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <p class="detail-label">Motivo</p>
          <p class="detail-value">{{ $appointment->reason }}</p>
        </div>
      </div>
      @endif
      <div class="detail-row">
        <div class="detail-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00BFA6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <p class="detail-label">Duración</p>
          <p class="detail-value">{{ $appointment->duration_minutes }} minutos</p>
        </div>
      </div>
    </div>

    <div class="cta">
      <a href="{{ url('/appointments/confirm/' . $appointment->confirmation_token) }}" class="cta-btn">
        ✓ &nbsp;Confirmar mi cita
      </a>
    </div>

    <p class="notice">Si no puedes asistir, comunícate directamente con la clínica.</p>
    <p class="notice">Este enlace expira en 7 días y solo puede usarse una vez.</p>
    <br/>
    <p class="link-fallback">{{ url('/appointments/confirm/' . $appointment->confirmation_token) }}</p>
  </div>

  <div class="footer">
    <p>
      Este correo fue enviado por <strong style="color:#8fa3bf">{{ $appointment->clinic->name }}</strong> a través de Dentarix.<br/>
      Si no esperabas este mensaje, puedes ignorarlo.
    </p>
  </div>
</div>
</body>
</html>
