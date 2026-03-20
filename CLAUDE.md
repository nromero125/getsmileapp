# Dentaris — Instrucciones para Claude

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Vue 3 + Inertia.js v2, Vite 7 |
| CSS | Tailwind CSS 3 |
| BD (dev) | SQLite |
| BD (prod) | MySQL |
| Node.js | **22+** requerido (Vite 7 usa `crypto.hash`) |

## Comandos esenciales

```bash
# Levantar todo en paralelo (Laravel + Vite + queue + logs)
composer dev

# O por separado:
php artisan serve          # http://localhost:8000
npm run dev                # Vite HMR

# Build de producción (siempre con Node 22)
source ~/.nvm/nvm.sh && nvm use 22 && npm run build

# Migraciones
php artisan migrate
php artisan migrate --seed   # con datos de prueba

# Tests
composer test
```

## Credenciales de prueba

| Rol | Email | Password |
|-----|-------|----------|
| Admin | admin@dentaris.app | password |
| Dentista | james@dentaris.app | password |
| Recepcionista | receptionist@dentaris.app | password |

## Arquitectura

### Multi-clínica (SaaS)
Todos los modelos tienen `clinic_id`. Siempre filtrar por `auth()->user()->clinic_id`. Nunca omitir este scoping.

### RBAC (roles)
- **admin** — acceso total
- **dentist** — clínico (odontograma, diagnósticos, notas)
- **receptionist** — facturación (facturas, cotizaciones, pagos)

Gates definidos en `AuthServiceProvider`: `admin`, `clinical` (dentist + admin), `billing` (receptionist + admin).
En rutas: `->middleware('can:admin')`, `->middleware('can:clinical')`, etc.
En Vue: `usePage().props.can.clinical`, `.can.billing`, `.can.admin`.

### Inertia + Vue
- Páginas en `resources/js/Pages/`
- Layouts en `resources/js/Layouts/AppLayout.vue`
- Componentes reutilizables en `resources/js/Components/`
- El servidor pasa props con `Inertia::render('PageName', [...])`
- `HandleInertiaRequests` comparte `auth.user`, `auth.clinic`, `can`, `flash` globalmente

## Convenciones críticas

### `useForm` y el campo `name`
Inertia reserva `name` internamente en `useForm`. **Nunca usar `name` como clave de useForm.** Usar nombres descriptivos y remapear antes de enviar:

```js
const form = useForm({
  clinic_name: '',   // NO: name: ''
  item_name: '',
  diag_name: '',
})

// Remapear al enviar:
form.transform(() => ({ name: form.clinic_name, ... })).post(route(...))
```

### Uploads de archivos con Inertia
No usar `form.put()` con `forceFormData`. El método correcto:

```js
const form = useForm({ _method: 'put', logo: null, ... })
form.post(route('...'))  // Laravel recibe _method=put y lo convierte
```

### Orden de rutas (crítico)
Registrar rutas estáticas **antes** de wildcards, o `create` se interpreta como `{id}`:

```php
// ✅ Correcto
Route::get('/invoices/create', [...]);   // primero
Route::get('/invoices/{invoice}', [...]);  // después

// ❌ Roto — 404 en /create
Route::get('/invoices/{invoice}', [...]);
Route::get('/invoices/create', [...]);
```

## Modelos principales

```
Clinic ──< User
       ──< Patient ──< Appointment ──< Treatment (pivot)
                   ──< Invoice ──< InvoiceItem
                              ──< Payment
                              ──< InvoiceInstallment
                   ──< Quote ──< QuoteItem
                   ──< ToothDiagnosis ──> DiagnosisCatalog ──> Treatment (pivot)
                   ──< DentalRecord (sistema legacy, no tocar)
InventoryItem ──< InventoryMovement
              ──> Treatment (pivot: quantity_used)
DiagnosisCatalog ──> Treatment (pivot: tratamientos sugeridos)
```

### Modelos con `$appends` importantes
- `Clinic` → `logo_url` (necesario para props de Inertia)
- `InventoryItem` → `is_low_stock`
- `InvoiceInstallment` → `status` (paid/overdue/pending, calculado)

## Estructura de rutas (`routes/web.php`)

| Grupo | Middleware | Prefijo |
|-------|-----------|---------|
| Dashboard, perfil, pacientes, citas | `auth, verified` | — |
| Odontograma, diagnósticos dentales | `auth, verified, can:clinical` | — |
| Facturas, cotizaciones, pagos | `auth, verified` (crear/pagar: `can:billing`) | — |
| Inventario (ver + movimientos) | `auth, verified` | — |
| Inventario CRUD | `auth, verified, can:admin` | — |
| Config clínica, staff, tratamientos, catálogo diagnósticos | `auth, verified, can:admin` | — |
| Confirmación cita paciente | público (sin auth) | — |
| API calendario | `auth, verified` | `/api` |

## Tablas de base de datos

```
clinics, users, password_reset_tokens, sessions
patients
treatment_categories, treatments
appointments, appointment_treatments
dental_records (legacy), clinical_notes
invoices, invoice_items, payments, invoice_installments
quotes, quote_items
patient_files, media (Spatie)
inventory_categories, inventory_items, inventory_movements, inventory_item_treatment
diagnosis_catalog, diagnosis_catalog_treatment, tooth_diagnoses
cache, jobs
```

## Módulos del sistema

### Pacientes (`/patients`)
CRUD completo. Ver/crear/editar: todos los roles. Eliminar: solo `billing`.
Show page tiene tabs: Resumen | Citas | Odontograma (si `can.clinical`) | Facturación | Archivos.
Archivos gestionados con Spatie MediaLibrary (`xrays`, `photos`, `documents`, `other`).

### Odontograma (`/patients/{id}/odontogram`)
Sistema de numeración universal (dientes 1–32). Solo `can:clinical`.
- **Sistema nuevo**: `tooth_diagnoses` ← catálogo de diagnósticos por clínica (`diagnosis_catalog`)
- **Sistema legacy**: `dental_records` — existente pero ya no se usa en el odontograma principal
- Selección múltiple de dientes para registrar un diagnóstico en varias piezas a la vez
- El catálogo de diagnósticos se administra en `/diagnosis-catalog` (admin)

### Facturación (`/invoices`)
- Formato número: `INV-00001` (auto)
- Estados: `draft → sent → paid / partial / overdue / cancelled`
- Soporta pagos múltiples y planes de cuotas (installments)
- PDF con DomPDF: `GET /invoices/{id}/pdf`
- Cuentas por cobrar con aging: `/receivables`

### Cotizaciones (`/quotes`)
- Formato: `COT-00001`
- Estados: `draft → sent → accepted/rejected/expired`
- Se pueden convertir en factura: `POST /quotes/{id}/convert`

### Inventario (`/inventory`)
- Movimientos: `in` (entrada), `out` (salida), `adjustment` (ajuste absoluto)
- Alertas de stock bajo: `is_low_stock` = `stock <= min_stock`
- Relación insumo → tratamiento con `quantity_used` en pivot

### Tratamientos (`/treatments`)
Admin-only. Scoped por clínica. Cada clínica gestiona su propio catálogo con precios.

## Paleta de colores (Tailwind)

- `navy-*` — Color principal oscuro (`#0F1F3D` base)
- `teal-*` — Acento (`#00BFA6`)
- `cream-*` — Fondo claro

## Patrones de componentes Vue

```vue
<!-- Clases CSS utilitarias del proyecto -->
<div class="card">          <!-- Tarjeta estándar -->
<button class="btn-primary"> <!-- Botón principal teal -->
<button class="btn-outline"> <!-- Botón secundario -->
<button class="btn-ghost">   <!-- Botón sin borde -->
<button class="btn-danger">  <!-- Botón rojo destructivo -->
<input class="input">        <!-- Campo de formulario -->
<label class="label">        <!-- Etiqueta de campo -->
<span class="badge">         <!-- Etiqueta de estado -->
<tr class="table-row">       <!-- Fila de tabla con hover -->
```

Componentes disponibles: `Modal`, `ConfirmModal`, `StatusBadge`.

## Notas de desarrollo

- La app está en **español** (locale `es`). Todos los textos de UI en español.
- Dark mode: toggle en header, guarda en `localStorage('theme')`.
- Flash messages: `back()->with('success', '...')` o `->with('error', '...')`. El layout los muestra automáticamente.
- Para confirmar email de cita: `AppointmentController::sendConfirmation()` usa `confirmation_token`.
