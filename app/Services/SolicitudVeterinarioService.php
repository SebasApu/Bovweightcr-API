<?php

namespace App\Services;

use App\Contracts\IFincaRepository;
use App\Contracts\ISolicitudVeterinarioRepository;
use App\Contracts\IUserRepository;
use App\Events\SolicitudVeterinarioAprobada;
use App\Events\SolicitudVeterinarioRechazada;
use App\Models\SolicitudVeterinario;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SolicitudVeterinarioService
{
    public function __construct(
        private readonly ISolicitudVeterinarioRepository $solicitudes,
        private readonly IFincaRepository $fincas,
        private readonly IUserRepository $usuarios,
    ) {}

    public function crear(array $datos, User $ganadero): SolicitudVeterinario
    {
        $finca = $this->fincas->findById($datos['finca_id']);

        if (! $finca) {
            throw new NotFoundHttpException('Finca no encontrada.');
        }

        if ((int) $finca->usuario_id !== $ganadero->id) {
            throw new UnprocessableEntityHttpException('No tiene permiso para solicitar veterinario en esta finca.');
        }

        $veterinario = $this->usuarios->findByEmail($datos['correo_veterinario']);

        if (! $veterinario) {
            throw new NotFoundHttpException('No existe un usuario registrado con ese correo electrónico.');
        }

        $veterinario->loadMissing('tipoUsuario');

        if ($veterinario->tipoUsuario?->nombre !== 'Veterinario') {
            throw new UnprocessableEntityHttpException('El usuario con ese correo no tiene rol de Veterinario.');
        }

        $solicitud = new SolicitudVeterinario([
            'finca_id'       => $datos['finca_id'],
            'ganadero_id'    => $ganadero->id,
            'veterinario_id' => $veterinario->id,
            'estado'         => 'pendiente',
        ]);

        return $this->solicitudes->save($solicitud);
    }

    public function listar(): Collection
    {
        return $this->solicitudes->findAll();
    }

    public function listarPendientes(): Collection
    {
        return $this->solicitudes->findPendientes();
    }

    public function obtener(int $id): SolicitudVeterinario
    {
        $solicitud = $this->solicitudes->findById($id);

        if (! $solicitud) {
            throw new NotFoundHttpException('Solicitud no encontrada.');
        }

        return $solicitud;
    }

    public function revisar(int $id, string $decision, User $admin): SolicitudVeterinario
    {
        $solicitud = $this->obtener($id);

        if ($solicitud->estado !== 'pendiente') {
            throw new UnprocessableEntityHttpException('La solicitud ya fue revisada.');
        }

        if ($decision === 'aprobar') {
            return $this->aprobar($solicitud, $admin);
        }

        return $this->rechazar($solicitud, $admin);
    }

    // ── Privados ─────────────────────────────────────────────────────────────

    private function aprobar(SolicitudVeterinario $solicitud, User $admin): SolicitudVeterinario
    {
        return DB::transaction(function () use ($solicitud, $admin) {
            $solicitud = SolicitudVeterinario::lockForUpdate()->findOrFail($solicitud->id);

            if ($solicitud->estado !== 'pendiente') {
                throw new UnprocessableEntityHttpException('La solicitud ya fue revisada.');
            }

            $finca = $this->fincas->findById($solicitud->finca_id);
            $finca->veterinario_id = $solicitud->veterinario_id;
            $this->fincas->save($finca);

            $solicitud->estado       = 'aprobado';
            $solicitud->aprobado_en  = now();
            $solicitud->aprobado_por = $admin->id;
            $resultado = $this->solicitudes->save($solicitud);

            SolicitudVeterinarioAprobada::dispatch($resultado);

            return $resultado;
        });
    }

    private function rechazar(SolicitudVeterinario $solicitud, User $admin): SolicitudVeterinario
    {
        $solicitud->estado       = 'rechazado';
        $solicitud->aprobado_por = $admin->id;
        $resultado = $this->solicitudes->save($solicitud);

        SolicitudVeterinarioRechazada::dispatch($resultado);

        return $resultado;
    }
}
