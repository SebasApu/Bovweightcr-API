<?php

namespace App\Services;

use App\Contracts\IGanadoFactory;
use App\Contracts\IGanadoRepository;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\RegistroPeso;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GanadoService
{
    public function __construct(
        private readonly IGanadoRepository $ganados,
        private readonly IGanadoFactory $ganadoFactory,
    ) {}

    public function listarTodos(User $user): Collection
    {
        $user->loadMissing('tipoUsuario');

        if ($user->esAdministrador()) {
            return $this->ganados->findAll();
        }

        if ($user->tipoUsuario?->nombre === 'Veterinario') {
            return $this->ganados->findAllByVeterinario($user->id);
        }

        return $this->ganados->findAllByUsuario($user->id);
    }

    public function listarPorFinca(int $fincaId, User $user): Collection
    {
        $this->verificarAccesoFinca($fincaId, $user);

        return $this->ganados->findByFincaId($fincaId);
    }

    public function obtener(int $id): Ganado
    {
        $ganado = $this->ganados->findById($id);

        if (! $ganado) {
            throw new NotFoundHttpException('Animal no encontrado.');
        }

        return $ganado;
    }

    public function crear(array $datos, User $user): Ganado
    {
        $this->verificarPropiedadFinca($datos['finca_id'], $user);

        if ($this->ganados->existsByArete($datos['arete'])) {
            throw new ConflictHttpException('Ya existe un animal con ese número de arete.');
        }

        $ganado = $this->ganadoFactory->make($datos);

        return $this->ganados->save($ganado);
    }

    public function actualizar(int $id, array $datos, User $user): Ganado
    {
        $ganado = $this->obtener($id);

        $this->verificarPropiedadFinca($ganado->finca_id, $user);

        if ($this->ganados->existsByArete($datos['arete'], $id)) {
            throw new ConflictHttpException('Ya existe otro animal con ese número de arete.');
        }

        $ganado->fill($datos);

        return $this->ganados->save($ganado);
    }

    public function eliminar(int $id): void
    {
        $this->obtener($id);

        $this->ganados->delete($id);
    }

    /**
     * Registra un peso manual para el animal (queda en el historial de pesos).
     */
    public function registrarPeso(int $ganadoId, float $peso, User $user): RegistroPeso
    {
        $ganado = $this->obtener($ganadoId);

        $this->verificarPropiedadFinca($ganado->finca_id, $user);

        return RegistroPeso::create([
            'ganado_id'      => $ganadoId,
            'peso_estimado'  => $peso,
            'peso_corregido' => $peso,
            'fecha'          => now(),
            'metodo'         => 'manual',
        ]);
    }

    /**
     * Actualiza el estado de salud del animal. Acceso exclusivo del
     * veterinario asignado a la finca del animal (ni el dueño ni otro
     * veterinario pueden usar esta operación).
     */
    public function actualizarEstadoSalud(int $id, int $estadoSaludId, User $user): Ganado
    {
        $ganado = $this->obtener($id);

        $this->verificarVeterinarioAsignado($ganado->finca_id, $user);

        $ganado->estado_salud_id = $estadoSaludId;

        return $this->ganados->save($ganado);
    }

    /**
     * Acceso de LECTURA: permite al dueño de la finca o al veterinario asignado.
     */
    private function verificarAccesoFinca(int $fincaId, User $user): void
    {
        $finca = Finca::find($fincaId);

        if (! $finca) {
            throw new NotFoundHttpException('Finca no encontrada.');
        }

        $user->loadMissing('tipoUsuario');

        if ($user->esAdministrador()) {
            return;
        }

        $esDueno         = $finca->usuario_id === $user->id;
        $esVetAsignado   = $finca->veterinario_id === $user->id;

        if (! $esDueno && ! $esVetAsignado) {
            throw new AccessDeniedHttpException('No tienes acceso a esta finca.');
        }
    }

    /**
     * Acceso de ESCRITURA: solo el dueño de la finca (crear/editar/borrar ganado).
     */
    private function verificarPropiedadFinca(int $fincaId, User $user): void
    {
        $finca = Finca::find($fincaId);

        if (! $finca) {
            throw new NotFoundHttpException('Finca no encontrada.');
        }

        if ($finca->usuario_id !== $user->id) {
            throw new AccessDeniedHttpException('No tienes acceso a esta finca.');
        }
    }

    /**
     * Acceso de ESCRITURA exclusivo del veterinario asignado (solo para
     * cambiar el estado de salud, no para el resto del animal).
     */
    private function verificarVeterinarioAsignado(int $fincaId, User $user): void
    {
        $user->loadMissing('tipoUsuario');

        $finca = Finca::find($fincaId);

        if (! $finca) {
            throw new NotFoundHttpException('Finca no encontrada.');
        }

        $esVeterinario = $user->tipoUsuario?->nombre === 'Veterinario';
        $esAsignado    = $finca->veterinario_id === $user->id;

        if (! $esVeterinario || ! $esAsignado) {
            throw new AccessDeniedHttpException('Solo el veterinario asignado a esta finca puede actualizar el estado de salud.');
        }
    }
}
