<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

trait AgendaTestTrait
{
    protected function seedAgenda(): void
    {
        $db = $this->getTableLocator()->get('Agendamentos')->getConnection();
        $stamps = ['created' => '2026-11-19 12:00:00', 'modified' => '2026-11-19 12:00:00', 'deleted' => null];
        $db->insert('users', [
            'id' => 10, 'cpf' => 100, 'nome' => 'Servidor técnico', 'email' => 'servidor@example.com',
            'password' => 'hash', 'telefone' => 1, 'tipo_id' => 1, 'setor_id' => 1, 'agendar' => true,
        ] + $stamps);
        $db->update('alunos', ['nome' => 'Bruno', 'curso' => 'Informática'], ['id' => 1]);
        $db->insert('alunos', [
            'id' => 2, 'matricula' => 2, 'nome' => 'Ana', 'curso' => 'Informática',
            'situacao' => 'Matriculado', 'emaila' => '', 'emailp' => '', 'anop' => '2026/2',
        ] + $stamps);
        $db->insert('responsaveis', ['id' => 2, 'users_id' => 1, 'alunos_id' => 2] + $stamps);
    }

    protected function form(): array
    {
        return [
            'servidor_id' => 10, 'responsavel_id' => 1, 'horario_id' => 1,
            'data' => '2026-11-21', 'hora' => '14:30:00', 'assunto' => 'Reunião',
        ];
    }

    protected function responseData(): array
    {
        return json_decode((string)$this->_response->getBody(), true)['data'];
    }
}
