<?php

namespace App\Services;

use App\Contracts\IFincaFactory;
use App\Contracts\IFincaRepository;
use App\Contracts\IUserRepository;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Lógica de negocio del CRUD de fincas.
 */
class FincaService
{
    public function __construct(
        private readonly IFincaRepository $fincas,
        private readonly IFincaFactory $fincaFactory,
        private readonly IUserRepository $usuarios,
    ) {}

    public function listar(User $user): Collection
    {
        $user->loadMissing('tipoUsuario');

        if ($user->esAdministrador()) {
            return $this->fincas->findAll();
        }

        if ($user->tipoUsuario?->nombre === 'Veterinario') {
            return $this->fincas->findByVeterinarioId($user->id);
        }

        return $this->fincas->findByUsuarioId($user->id);
    }

    public function obtener(int $id): Finca
    {
        $finca = $this->fincas->findById($id);

        if (! $finca) {
            throw new NotFoundHttpException('Finca no encontrada.');
        }

        return $finca;
    }

    public function crear(array $datos): Finca
    {
        if ($this->fincas->existsByNumeroFinca($datos['numero_finca'])) {
            throw new ConflictHttpException('Ya existe una finca con ese número.');
        }

        $finca = $this->fincaFactory->make($datos);

        return $this->fincas->save($finca);
    }

    public function actualizar(int $id, array $datos): Finca
    {
        $finca = $this->obtener($id);

        if ($this->fincas->existsByNumeroFinca($datos['numero_finca'], $id)) {
            throw new ConflictHttpException('Ya existe otra finca con ese número.');
        }

        $finca->fill($datos);

        return $this->fincas->save($finca);
    }

    public function eliminar(int $id): void
    {
        $finca = $this->obtener($id);

        if ($finca->ganados()->exists()) {
            throw new BadRequestHttpException('No se puede eliminar la finca porque tiene ganado asociado');
        }

        $this->fincas->delete($id);
    }

    public function asignarGanadero(int $fincaId, int $usuarioId): Finca
    {
        $finca = $this->obtener($fincaId);

        $usuario = $this->usuarios->findById($usuarioId);

        if (! $usuario) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        $usuario->loadMissing('tipoUsuario');

        if ($usuario->tipoUsuario?->nombre !== 'Ganadero') {
            throw new UnprocessableEntityHttpException('El usuario no tiene rol de Ganadero.');
        }

        $finca->usuario_id = $usuarioId;

        return $this->fincas->save($finca);
    }

    public function removerGanadero(int $fincaId): Finca
    {
        $finca = $this->obtener($fincaId);
        $finca->usuario_id = null;

        return $this->fincas->save($finca);
    }

    public function asignarVeterinario(int $fincaId, int $usuarioId): Finca
    {
        $finca = $this->obtener($fincaId);

        $usuario = $this->usuarios->findById($usuarioId);

        if (! $usuario) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        $usuario->loadMissing('tipoUsuario');

        if ($usuario->tipoUsuario?->nombre !== 'Veterinario') {
            throw new UnprocessableEntityHttpException('El usuario no tiene rol de Veterinario.');
        }

        $finca->veterinario_id = $usuarioId;

        return $this->fincas->save($finca);
    }

    public function removerVeterinario(int $fincaId): Finca
    {
        $finca = $this->obtener($fincaId);
        $finca->veterinario_id = null;

        return $this->fincas->save($finca);
    }
}
