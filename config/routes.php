<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        $builder->connect('/pages/*', 'Pages::display');
        $builder->connect('/login/entrar', ['prefix' => 'Api', 'controller' => 'Users', 'action' => 'logar']);
        $builder->fallbacks();
    });

    $routes->prefix('Api', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json']);
        $controllers = [
            'perfis' => 'Perfis', 'perfil' => 'Perfis', 'usuarios' => 'Users',
            'alunos' => 'Alunos', 'responsaveis' => 'Responsaveis',
        ];
        foreach ($controllers as $path => $controller) {
            $builder->connect("/{$path}/adicionar", ['controller' => $controller, 'action' => 'add']);
            $builder->connect("/{$path}/listar", ['controller' => $controller, 'action' => 'list']);
            $actions = [
                'buscar' => 'findById', 'listar' => 'findById', 'editar' => 'edit',
                'deletar' => 'delete', 'desativar' => 'delete', 'reativar' => 'active',
            ];
            foreach ($actions as $pathAction => $action) {
                $builder->connect("/{$path}/{$pathAction}/{id}", compact('controller', 'action'))
                    ->setPass(['id'])->setPatterns(['id' => '\d+']);
            }
        }
        $builder->connect('/usuarios/entrar', ['controller' => 'Users', 'action' => 'logar']);
        $builder->connect('/alunos/importar', ['controller' => 'Alunos', 'action' => 'import']);
        $builder->connect('/alunos/findmat/{matricula}', ['controller' => 'Alunos', 'action' => 'findByMat'])
            ->setPass(['matricula'])->setPatterns(['matricula' => '\d+']);
        $builder->connect('/alunos/findname/{name}', ['controller' => 'Alunos', 'action' => 'findByName'])
            ->setPass(['name'])->setPatterns(['name' => '[^/]+']);
    });
};
