<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'is_active',
        'email_verified_at',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Indica que la clave primaria no es autoincremental.
     * @var bool
     */
    public $incrementing = false;

    /**
     * El tipo de la clave primaria.
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Verificar si el usuario puede acceder al panel de Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin') && $this->is_active;
    }

    /**
     * Obtener el nombre completo del usuario para Filament
     */
    public function getFilamentName(): string
    {
        return $this->getUserName();
    }

    /**
     * Método para obtener el nombre del usuario
     */
    public function getUserName(): string
    {
        // Si tenemos first_name y last_name, construir el nombre completo
        if ($this->first_name || $this->last_name) {
            return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        }
        
        // Fallback al campo name o email
        return $this->attributes['name'] ?? $this->email ?? 'Usuario Sin Nombre';
    }

    /**
     * Accessor para el atributo name (usado por Filament)
     */
    public function getNameAttribute(): string
    {
        return $this->getUserName();
    }

    /**
     * Mutator para first_name - actualiza el campo name automáticamente
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = $value;
        $this->updateNameField();
    }

    /**
     * Mutator para last_name - actualiza el campo name automáticamente
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = $value;
        $this->updateNameField();
    }

    /**
     * Actualiza el campo name basado en first_name y last_name
     */
    protected function updateNameField()
    {
        if (isset($this->attributes['first_name']) || isset($this->attributes['last_name'])) {
            $firstName = $this->attributes['first_name'] ?? '';
            $lastName = $this->attributes['last_name'] ?? '';
            $this->attributes['name'] = trim($firstName . ' ' . $lastName);
        }
    }

    /**
     * Relación con direcciones
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Relación con pedidos
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación con carritos
     */
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Verificar si el usuario es admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}