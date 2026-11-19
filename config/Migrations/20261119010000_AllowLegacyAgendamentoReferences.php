<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Mantém o histórico sem exigir vínculos do modelo antigo em novas reuniões.
 */
class AllowLegacyAgendamentoReferences extends BaseMigration
{
    public function change(): void
    {
        $this->table('agendamentos')
            ->changeColumn('responsavel_vinculo_id', 'integer', ['null' => true, 'default' => null])
            ->changeColumn('coordenador_id', 'integer', ['null' => true, 'default' => null])
            ->update();
    }
}
