<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Exception;
use InvalidArgumentException;

/**
 * Horarios Controller
 */
class HorariosController extends AppController
{
    /**
     * Cadastra o horário com os campos permitidos.
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';

        try {
            $form = array_intersect_key($this->request->getData(), array_flip([
                'setor_id', 'setor_nome', 'setor', 'dia_semana', 'hora_inicio', 'hora_fim',
            ]));
            $form = $this->resolverReferencia($form, 'Setors', 'setor_id', 'setor_nome', ['setor']);

            $novoRegistro = $tabela->newEmptyEntity();
            $novoRegistro = $tabela->patchEntity($novoRegistro, $form);

            if ($tabela->save($novoRegistro)) {
                $result = $novoRegistro;
                $message = 'Registro salvo com sucesso.';
                $statusCode = 201;
            } else {
                $errors = $novoRegistro->getErrors();
            }
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Lista os horários de setores ativos e aplica os filtros recebidos.
     */
    public function list(?string $id = null): Response
    {
        $this->request->allowMethod(['get', 'post']);
        if ($id !== null) {
            return $this->find($id);
        }
        $statusCode = 200;

        try {
            $tabela = $this->fetchTable('Horarios');
            $form = $this->request->getData() + $this->request->getQueryParams();
            $conditions = ['Horarios.deleted IS' => null, 'Setores.deleted IS' => null];
            foreach (['setor_id', 'dia_semana', 'setor_nome'] as $field) {
                if (!isset($form[$field]) || $form[$field] === '') {
                    continue;
                }
                if ($field === 'setor_nome') {
                    if (!is_string($form[$field]) || trim($form[$field]) === '') {
                        return $this->json(['message' => 'Informe um nome de setor válido.'], 422);
                    }
                    $conditions['Setores.nome'] = trim($form[$field]);
                    continue;
                }
                if (!is_scalar($form[$field]) || !ctype_digit((string)$form[$field]) || (int)$form[$field] < 1) {
                    return $this->json(['message' => 'Filtro inválido: ' . $field], 422);
                }
                if ($field === 'dia_semana' && (int)$form[$field] > 7) {
                    return $this->json(['message' => 'Use dia_semana entre 1 e 7.'], 422);
                }
                $conditions['Horarios.' . $field] = (int)$form[$field];
            }
            $result = $tabela->find()->contain(['Setores'])->where($conditions)->orderBy([
                'Horarios.dia_semana' => 'ASC', 'Horarios.hora_inicio' => 'ASC', 'Horarios.id' => 'ASC',
            ])->all();
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Consulta o horário pelo próprio ID, incluindo registros desativados.
     */
    public function find(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $tabela = $this->fetchTable('Horarios');
            $registro = $this->localizarRegistro('Horarios', $id);
            $result = $tabela->get($registro->id, contain: ['Setores']);
        } catch (RecordNotFoundException $e) {
            return $this->json(['message' => 'Registro não encontrado!', 'code' => 404], 404);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Altera o horário identificado na rota.
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';

        try {
            $registro = $this->localizarRegistro('Horarios', $id);
            $form = array_intersect_key($this->request->getData(), array_flip([
                'setor_id', 'setor_nome', 'setor', 'dia_semana', 'hora_inicio', 'hora_fim',
            ]));
            $form = $this->resolverReferencia($form, 'Setors', 'setor_id', 'setor_nome', ['setor']);

            $registro = $tabela->patchEntity($registro, $form);

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Desativa o horário preservando seu histórico.
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';

        try {
            $registro = $this->localizarRegistro('Horarios', $id);
            $registro['deleted'] = DateTime::now()->format('Y-m-d H:i:s');

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Reativa o horário existente.
     */
    public function active(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $tabela = $this->fetchTable('Horarios');
        $statusCode = 422;
        $result = null;
        $errors = [];
        $message = 'Não foi possível salvar o registro.';

        try {
            $registro = $this->localizarRegistro('Horarios', $id);
            $registro['deleted'] = null;

            if ($tabela->save($registro)) {
                $result = $registro;
                $message = 'Registro salvo com sucesso.';
                $statusCode = 200;
            } else {
                $errors = $registro->getErrors();
            }
        } catch (RecordNotFoundException $e) {
            $message = 'Registro não encontrado!';
            $statusCode = 404;
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $statusCode = 500;
        }

        return $this->json(['data' => $result, 'message' => $message, 'errors' => $errors], $statusCode);
    }

    /**
     * Busca os horários ativos pelo dia da semana ISO.
     */
    public function findByDia(?string $dia = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            if (!ctype_digit((string)$dia) || (int)$dia < 1 || (int)$dia > 7) {
                return $this->json(['message' => 'Use dia_semana de 1 (segunda) a 7 (domingo).'], 422);
            }
            $tabela = $this->fetchTable('Horarios');
            $result = $tabela->find()->contain(['Setores'])->where([
                'Horarios.dia_semana' => (int)$dia, 'Horarios.deleted IS' => null, 'Setores.deleted IS' => null,
            ])->orderBy([
                'Horarios.hora_inicio' => 'ASC', 'Horarios.id' => 'ASC',
            ])->all();
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }

    /**
     * Busca os horários ativos de um setor.
     */
    public function findBySetor(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $statusCode = 200;

        try {
            $tabela = $this->fetchTable('Horarios');
            $porNome = (bool)$this->request->getParam('porNome');
            $coluna = $porNome ? 'Setores.nome' : 'Horarios.setor_id';
            $result = $tabela->find()->contain(['Setores'])->where([
                $coluna => $porNome ? trim((string)$id) : (int)$id,
                'Horarios.deleted IS' => null, 'Setores.deleted IS' => null,
            ])->orderBy([
                'Horarios.dia_semana' => 'ASC', 'Horarios.hora_inicio' => 'ASC', 'Horarios.id' => 'ASC',
            ])->all();
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'data' => null, 'message' => $e->getMessage(), 'errors' => ['referencia' => [$e->getMessage()]],
            ], 422);
        } catch (Exception $e) {
            return $this->json(['message' => 'Erro: ' . $e->getMessage()], 500);
        }

        return $this->json(['data' => $result], $statusCode);
    }
}
