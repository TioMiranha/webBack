<?php

/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */

return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        /*
         * Here, we are connecting '/' (base path) to a controller called 'Pages',
         * its action called 'display', and we pass a param to select the view file
         * to use (in this case, templates/Pages/home.php)...
         */
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        $builder->connect('/pages/*', 'Pages::display');
        $builder->connect('/login/entrar', ['prefix' => 'Api', 'controller' => 'Users', 'action' => 'logar']);

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
        $builder->fallbacks();
    });

    $routes->prefix('Api', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json']);

        // Tipos
        $builder->connect('/perfil/adicionar', ['controller' => 'Perfis', 'action' => 'add']);
        $builder->connect('/perfil/listar', ['controller' => 'Perfis', 'action' => 'list']);
        $builder->connect('/perfil/buscar/{id}', ['controller' => 'Perfis', 'action' => 'findById'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfil/listar/{id}', ['controller' => 'Perfis', 'action' => 'findById'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfil/editar/{id}', ['controller' => 'Perfis', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfil/deletar/{id}', ['controller' => 'Perfis', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfil/desativar/{id}', ['controller' => 'Perfis', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfil/reativar/{id}', ['controller' => 'Perfis', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Perfis (alias no plural)
        $builder->connect('/perfis/adicionar', ['controller' => 'Perfis', 'action' => 'add']);
        $builder->connect('/perfis/listar', ['controller' => 'Perfis', 'action' => 'list']);
        $builder->connect('/perfis/buscar/{id}', ['controller' => 'Perfis', 'action' => 'findById'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfis/listar/{id}', ['controller' => 'Perfis', 'action' => 'findById'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfis/editar/{id}', ['controller' => 'Perfis', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfis/deletar/{id}', ['controller' => 'Perfis', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfis/desativar/{id}', ['controller' => 'Perfis', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/perfis/reativar/{id}', ['controller' => 'Perfis', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Usuarios
        $builder->connect('/usuarios/entrar', ['controller' => 'Users', 'action' => 'logar']);
        $builder->connect('/usuarios/adicionar', ['controller' => 'Users', 'action' => 'add']);
        $builder->connect('/usuarios/listar', ['controller' => 'Users', 'action' => 'list']);
        $builder->connect('/usuarios/editar/{id}', ['controller' => 'Users', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/usuarios/buscar/{id}', ['controller' => 'Users', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/usuarios/listar/{id}', ['controller' => 'Users', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/usuarios/deletar/{id}', ['controller' => 'Users', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/usuarios/desativar/{id}', ['controller' => 'Users', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/usuarios/reativar/{id}', ['controller' => 'Users', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Alunos
        $builder->connect('/alunos/importar', ['controller' => 'Alunos', 'action' => 'import']);
        $builder->connect('/alunos/adicionar', ['controller' => 'Alunos', 'action' => 'add']);
        $builder->connect('/alunos/listar', ['controller' => 'Alunos', 'action' => 'list']);
        $builder->connect('/alunos/findmat/{matricula}', ['controller' => 'Alunos', 'action' => 'findByMat'])->setPass(['matricula'])->setPatterns(['matricula' => '\d+']);
        $builder->connect('/alunos/findname/{name}', ['controller' => 'Alunos', 'action' => 'findByName'])->setPass(['name'])->setPatterns(['name' => "[^/]+"]);
        $builder->connect('/alunos/buscar/{id}', ['controller' => 'Alunos', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/alunos/listar/{id}', ['controller' => 'Alunos', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/alunos/editar/{id}', ['controller' => 'Alunos', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/alunos/deletar/{id}', ['controller' => 'Alunos', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/alunos/desativar/{id}', ['controller' => 'Alunos', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/alunos/reativar/{id}', ['controller' => 'Alunos', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Responsáveis
        $builder->connect('/responsaveis/adicionar', ['controller' => 'Responsaveis', 'action' => 'add']);
        $builder->connect('/responsaveis/listar', ['controller' => 'Responsaveis', 'action' => 'list']);
        $builder->connect('/responsaveis/buscar/{id}', ['controller' => 'Responsaveis', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/responsaveis/listar/{id}', ['controller' => 'Responsaveis', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/responsaveis/editar/{id}', ['controller' => 'Responsaveis', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/responsaveis/deletar/{id}', ['controller' => 'Responsaveis', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/responsaveis/desativar/{id}', ['controller' => 'Responsaveis', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/responsaveis/reativar/{id}', ['controller' => 'Responsaveis', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Horários de atendimento por setor
        $builder->connect('/horarios/adicionar', ['controller' => 'Horarios', 'action' => 'add']);
        $builder->connect('/horarios/listar', ['controller' => 'Horarios', 'action' => 'list']);
        $builder->connect('/horarios/buscar/dia/{dia}', ['controller' => 'Horarios', 'action' => 'findByDia'])->setPass(['dia'])->setPatterns(['dia' => '[^/]+']);
        $builder->connect('/horarios/buscar/setor/{id}', ['controller' => 'Horarios', 'action' => 'findBySetor'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/horarios/buscar/{id}', ['controller' => 'Horarios', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/horarios/listar/{id}', ['controller' => 'Horarios', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/horarios/editar/{id}', ['controller' => 'Horarios', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/horarios/deletar/{id}', ['controller' => 'Horarios', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/horarios/desativar/{id}', ['controller' => 'Horarios', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/horarios/reativar/{id}', ['controller' => 'Horarios', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Cursos
        $builder->connect('/cursos/adicionar', ['controller' => 'Cursos', 'action' => 'add']);
        $builder->connect('/cursos/listar', ['controller' => 'Cursos', 'action' => 'list']);
        $builder->connect('/cursos/buscar/{id}', ['controller' => 'Cursos', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/cursos/listar/{id}', ['controller' => 'Cursos', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/cursos/editar/{id}', ['controller' => 'Cursos', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/cursos/deletar/{id}', ['controller' => 'Cursos', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/cursos/desativar/{id}', ['controller' => 'Cursos', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/cursos/reativar/{id}', ['controller' => 'Cursos', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        $builder->connect('/coordenadores/adicionar', ['controller' => 'Coordenadores', 'action' => 'add']);
        $builder->connect('/coordenadores/listar', ['controller' => 'Coordenadores', 'action' => 'list']);
        $builder->connect('/coordenadores/buscar/{id}', ['controller' => 'Coordenadores', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/coordenadores/listar/{id}', ['controller' => 'Coordenadores', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/coordenadores/editar/{id}', ['controller' => 'Coordenadores', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/coordenadores/deletar/{id}', ['controller' => 'Coordenadores', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/coordenadores/desativar/{id}', ['controller' => 'Coordenadores', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/coordenadores/reativar/{id}', ['controller' => 'Coordenadores', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        $builder->connect('/disponibilidades/adicionar', ['controller' => 'Disponibilidades', 'action' => 'add']);
        $builder->connect('/disponibilidades/listar', ['controller' => 'Disponibilidades', 'action' => 'list']);
        $builder->connect('/disponibilidades/buscar/{id}', ['controller' => 'Disponibilidades', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/disponibilidades/listar/{id}', ['controller' => 'Disponibilidades', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/disponibilidades/editar/{id}', ['controller' => 'Disponibilidades', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/disponibilidades/deletar/{id}', ['controller' => 'Disponibilidades', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/disponibilidades/desativar/{id}', ['controller' => 'Disponibilidades', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/disponibilidades/reativar/{id}', ['controller' => 'Disponibilidades', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        $builder->connect('/agendamentos/adicionar', ['controller' => 'Agendamentos', 'action' => 'add']);
        $builder->connect('/agendamentos/listar', ['controller' => 'Agendamentos', 'action' => 'list']);
        $builder->connect('/agendamentos/buscar/servidor/{id}', ['controller' => 'Agendamentos', 'action' => 'findByServidor'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/buscar/data/{data}', ['controller' => 'Agendamentos', 'action' => 'findByData'])->setPass(['data'])->setPatterns(['data' => '[^/]+']);
        $builder->connect('/agendamentos/buscar/data/{id}/{data}', ['controller' => 'Agendamentos', 'action' => 'findByServidor'])->setPass(['id', 'data'])->setPatterns(['id' => '\d+', 'data' => '[^/]+']);
        $builder->connect('/agendamentos/buscar/responsavel/{id}', ['controller' => 'Agendamentos', 'action' => 'findByResponsavel'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/buscar/aluno/{id}', ['controller' => 'Agendamentos', 'action' => 'findByAluno'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/buscar/{id}', ['controller' => 'Agendamentos', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/cancelar/{id}', ['controller' => 'Agendamentos', 'action' => 'cancel'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/listar/{id}', ['controller' => 'Agendamentos', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/editar/{id}', ['controller' => 'Agendamentos', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/deletar/{id}', ['controller' => 'Agendamentos', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/desativar/{id}', ['controller' => 'Agendamentos', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/agendamentos/reativar/{id}', ['controller' => 'Agendamentos', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        $builder->connect('/setores/adicionar', ['controller' => 'Setores', 'action' => 'add']);
        $builder->connect('/setores/listar', ['controller' => 'Setores', 'action' => 'list']);
        $builder->connect('/setores/buscar/{id}', ['controller' => 'Setores', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/setores/listar/{id}', ['controller' => 'Setores', 'action' => 'find'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/setores/editar/{id}', ['controller' => 'Setores', 'action' => 'edit'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/setores/deletar/{id}', ['controller' => 'Setores', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/setores/desativar/{id}', ['controller' => 'Setores', 'action' => 'delete'])->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect('/setores/reativar/{id}', ['controller' => 'Setores', 'action' => 'active'])->setPass(['id'])->setPatterns(['id' => '\d+']);

        // Alternativas por nome e seletores; as rotas por ID continuam disponíveis.
        $builder->connect('/usuarios/buscar', ['controller' => 'Users', 'action' => 'find']);
        $builder->connect('/usuarios/buscar/nome/{id}', ['controller' => 'Users', 'action' => 'find', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/usuarios/editar', ['controller' => 'Users', 'action' => 'edit']);
        $builder->connect('/usuarios/editar/nome/{id}', ['controller' => 'Users', 'action' => 'edit', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/usuarios/deletar', ['controller' => 'Users', 'action' => 'delete']);
        $builder->connect('/usuarios/deletar/nome/{id}', ['controller' => 'Users', 'action' => 'delete', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/usuarios/desativar', ['controller' => 'Users', 'action' => 'delete']);
        $builder->connect('/usuarios/desativar/nome/{id}', ['controller' => 'Users', 'action' => 'delete', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/usuarios/reativar', ['controller' => 'Users', 'action' => 'active']);
        $builder->connect('/usuarios/reativar/nome/{id}', ['controller' => 'Users', 'action' => 'active', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/setores/buscar', ['controller' => 'Setores', 'action' => 'find']);
        $builder->connect('/setores/buscar/nome/{id}', ['controller' => 'Setores', 'action' => 'find', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/setores/editar', ['controller' => 'Setores', 'action' => 'edit']);
        $builder->connect('/setores/editar/nome/{id}', ['controller' => 'Setores', 'action' => 'edit', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/setores/deletar', ['controller' => 'Setores', 'action' => 'delete']);
        $builder->connect('/setores/deletar/nome/{id}', ['controller' => 'Setores', 'action' => 'delete', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/setores/desativar', ['controller' => 'Setores', 'action' => 'delete']);
        $builder->connect('/setores/desativar/nome/{id}', ['controller' => 'Setores', 'action' => 'delete', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/setores/reativar', ['controller' => 'Setores', 'action' => 'active']);
        $builder->connect('/setores/reativar/nome/{id}', ['controller' => 'Setores', 'action' => 'active', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/responsaveis/buscar', ['controller' => 'Responsaveis', 'action' => 'find']);
        $builder->connect('/responsaveis/editar', ['controller' => 'Responsaveis', 'action' => 'edit']);
        $builder->connect('/responsaveis/deletar', ['controller' => 'Responsaveis', 'action' => 'delete']);
        $builder->connect('/responsaveis/desativar', ['controller' => 'Responsaveis', 'action' => 'delete']);
        $builder->connect('/responsaveis/reativar', ['controller' => 'Responsaveis', 'action' => 'active']);
        $builder->connect('/horarios/buscar', ['controller' => 'Horarios', 'action' => 'find']);
        $builder->connect('/horarios/editar', ['controller' => 'Horarios', 'action' => 'edit']);
        $builder->connect('/horarios/deletar', ['controller' => 'Horarios', 'action' => 'delete']);
        $builder->connect('/horarios/desativar', ['controller' => 'Horarios', 'action' => 'delete']);
        $builder->connect('/horarios/reativar', ['controller' => 'Horarios', 'action' => 'active']);
        $builder->connect('/agendamentos/buscar', ['controller' => 'Agendamentos', 'action' => 'find']);
        $builder->connect('/agendamentos/editar', ['controller' => 'Agendamentos', 'action' => 'edit']);
        $builder->connect('/agendamentos/deletar', ['controller' => 'Agendamentos', 'action' => 'delete']);
        $builder->connect('/agendamentos/desativar', ['controller' => 'Agendamentos', 'action' => 'delete']);
        $builder->connect('/agendamentos/reativar', ['controller' => 'Agendamentos', 'action' => 'active']);
        $builder->connect('/agendamentos/cancelar', ['controller' => 'Agendamentos', 'action' => 'cancel']);
        $builder->connect('/agendamentos/buscar/servidor/nome/{id}', ['controller' => 'Agendamentos', 'action' => 'findByServidor', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/agendamentos/buscar/responsavel/nome/{id}', ['controller' => 'Agendamentos', 'action' => 'findByResponsavel', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/agendamentos/buscar/aluno/nome/{id}', ['controller' => 'Agendamentos', 'action' => 'findByAluno', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);
        $builder->connect('/horarios/buscar/setor/nome/{id}', ['controller' => 'Horarios', 'action' => 'findBySetor', 'porNome' => true])->setPass(['id'])->setPatterns(['id' => '[^/]+']);

        $builder->fallbacks();
    });

    /*
     * If you need a different set of middleware or none at all,
     * open new scope and define routes there.
     *
     * ```
     * $routes->scope('/api', function (RouteBuilder $builder): void {
     *     // No $builder->applyMiddleware() here.
     *
     *     // Parse specified extensions from URLs
     *     // $builder->setExtensions(['json', 'xml']);
     *
     *     // Connect API actions here.
     * });
     * ```
     */
};
