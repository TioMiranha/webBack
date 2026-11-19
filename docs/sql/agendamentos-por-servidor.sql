-- MySQL / MariaDB. Ajuste o nome ou use '%' para listar todos os servidores.
SET @nome_servidor = '%';

-- A subconsulta gera uma única linha por responsável antes do JOIN com a agenda.
-- Os cursos usam a MESMA ordenação dos alunos, inclusive quando dois alunos têm o mesmo curso.
SELECT
    a.id,
    a.data,
    a.hora,
    serv.nome AS servidor,
    resp.nome AS responsavel,
    COALESCE(estudantes.aluno, '') AS aluno,
    COALESCE(estudantes.curso, '') AS curso,
    a.cancelado_em
FROM agendamentos a
INNER JOIN users serv ON serv.id = a.servidor_id
INNER JOIN users resp ON resp.id = a.responsavel_id
LEFT JOIN (
    SELECT
        r.users_id,
        GROUP_CONCAT(al.nome ORDER BY al.nome, al.id SEPARATOR ', ') AS aluno,
        GROUP_CONCAT(COALESCE(c.nome, al.curso, '') ORDER BY al.nome, al.id SEPARATOR ', ') AS curso
    FROM responsaveis r
    INNER JOIN alunos al ON al.id = r.alunos_id AND al.deleted IS NULL
    LEFT JOIN cursos c ON c.id = al.curso_id
    WHERE r.deleted IS NULL
    GROUP BY r.users_id
) estudantes ON estudantes.users_id = a.responsavel_id
WHERE serv.nome LIKE CONVERT(@nome_servidor USING utf8mb4) COLLATE utf8mb4_unicode_ci
    AND a.deleted IS NULL
ORDER BY a.data, a.hora, a.id;
