<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determina si el usuario puede consultar el catálogo e inventario de infraestructura.
     * APOC, GTS y Administrador tienen acceso de consulta.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determina si el usuario puede consultar los detalles técnicos de un recurso.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->is_active;
    }

    /**
     * Determina si el usuario puede dar de alta nueva infraestructura.
     * Exclusivo para Administrador.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    /**
     * Determina si el usuario puede editar metadatos del catálogo de infraestructura.
     * Exclusivo para Administrador.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    /**
     * Determina si el usuario puede cambiar el estado operativo ("Dar de baja" / poner fuera de servicio).
     * Exclusivo para Administrador.
     */
    public function toggleStatus(User $user, Category $category): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    /**
     * Determina si el usuario puede eliminar físicamente un registro.
     * Solo Administrador y ÚNICAMENTE si no tiene incidencias históricas asociadas.
     */
    public function delete(User $user, Category $category): bool
    {
        if (! $user->is_active || ! $user->isAdmin()) {
            return false;
        }

        return $category->incidents()->count() === 0;
    }
}
