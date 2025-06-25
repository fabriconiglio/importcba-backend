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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'email_verified_at',
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
        return $this->role === 'admin' && $this->is_active;
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
        $firstName = $this->first_name ?? '';
        $lastName = $this->last_name ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        
        return !empty($fullName) ? $fullName : ($this->email ?? 'Usuario Sin Nombre');
    }

    /**
     * Accessor para el atributo name (usado por Filament)
     */
    public function getNameAttribute(): string
    {
        return $this->getUserName();
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
        return $this->role === 'admin';
    }

    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}