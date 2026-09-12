# F-03 — `role` and `status` are mass-assignable

| | |
|---|---|
| **Severity** | High |
| **OWASP** | A01:2021 Broken Access Control |
| **Ticket** | SCRUM-49 |
| **Files** | `app/Models/User.php`, `AuthController.php`, factories |
| **Source** | [`../owasp-auth-review.md`](../owasp-auth-review.md) §F-03 |

---

## Problem

`backend/app/Models/User.php` lists both `role` and `status` in `$fillable`:

```php
protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'phone',
    'avatar_url',
    'role',      // ← privilege field
    'status',    // ← access field
];
```

## Risk

**There is no exploit today.** `registerClient()` and `registerAgency()` build the create array field by field and hardcode both values, so client-supplied `role` is ignored.

The finding is that the model *permits* privilege escalation. A single line added anywhere later —

```php
$user->update($request->all());
User::create($request->validated());
```

— lets a client register or update themselves as `role: admin`, or reactivate a suspended account by setting `status: active`.

Right now the only thing preventing full privilege escalation is that nobody has written that line yet. That is developer discipline, not a control. With the agency and car modules growing and a profile-update endpoint likely coming, the odds of it appearing are high.

---

## Change

### 1. `backend/app/Models/User.php`

**Before**

```php
protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'phone',
    'avatar_url',
    'role',
    'status',
];
```

**After**

```php
protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'phone',
    'avatar_url',
];
```

### 2. `AuthController::registerClient()`

**Before**

```php
$user = User::create([
    'first_name' => $validated['first_name'],
    'last_name' => $validated['last_name'],
    'email' => $validated['email'],
    'password' => $validated['password'],
    'phone' => $validated['phone'] ?? null,

    'role' => 'client',
    'status' => 'active',
]);
```

**After**

```php
$user = new User([
    'first_name' => $validated['first_name'],
    'last_name' => $validated['last_name'],
    'email' => $validated['email'],
    'password' => $validated['password'],
    'phone' => $validated['phone'] ?? null,
]);

$user->role = 'client';
$user->status = 'active';
$user->save();
```

### 3. `AuthController::registerAgency()`

Same pattern, inside the existing `DB::transaction()` block:

```php
$user = new User([
    'first_name' => $validated['first_name'],
    'last_name' => $validated['last_name'],
    'email' => $validated['email'],
    'password' => $validated['password'],
    'phone' => $validated['phone'],
]);

$user->role = 'agency';
$user->status = 'active';
$user->save();
```

Direct property assignment bypasses `$fillable` entirely — which is the intent. These are server-assigned values, never client-supplied.

---

## Side effects

**Factories will silently stop setting these fields.** Anything like:

```php
User::factory()->create(['role' => 'agency'])
```

will now create a user with whatever the database default is — probably `null` — and the role middleware will reject them with 403. Many existing tests depend on this.

Fix it in `UserFactory` with a state using `forceFill()`, or switch those calls to `forceCreate()`. Check:

- `backend/database/factories/UserFactory.php`
- `backend/database/factories/AgencyFactory.php`
- `backend/database/seeders/AdminUserSeeder.php` — this one certainly sets `role: admin`
- `backend/database/seeders/DemoDataSeeder.php`

**Run the full suite after this change.** Expect failures until the factories are updated. All 73 backend tests must be green before pushing.

---

## Verification

```json
POST /api/register/client
{
  "first_name": "A",
  "last_name": "B",
  "email": "escalation@test.local",
  "password": "Str0ngpass1",
  "password_confirmation": "Str0ngpass1",
  "role": "admin",
  "status": "active"
}
```

| Check | Expected |
|---|---|
| Response | 201 |
| `role` column in the database for that user | `client` |
| `status` column | `active` |
| Same test with `"status": "suspended"` on an existing user update path | `status` unchanged |

Add this as an automated test — it is the kind of regression that reappears easily.

---

## Commit

```
SCRUM-49 fix: remove role and status from mass assignment (F-03)
```
