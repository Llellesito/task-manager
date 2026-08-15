<?php

require_once __DIR__ . '/config/db.php';


/*
|--------------------------------------------------------------------------
| PROCESAMIENTO DE FORMULARIOS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | CREAR TAREA
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dueAt = trim($_POST['due_at'] ?? '');

        if ($title !== '') {

            // Si no se introduce fecha, guardamos NULL.
            $dueAt = $dueAt !== '' ? $dueAt : null;

            $stmt = $pdo->prepare(
                "INSERT INTO tasks (title, description, due_at)
                 VALUES (:title, :description, :due_at)"
            );

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'due_at' => $dueAt
            ]);
        }

        header('Location: index.php');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETAR / DESCOMPLETAR TAREA
    |--------------------------------------------------------------------------
    */

    if ($action === 'toggle') {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        if ($id) {

            $stmt = $pdo->prepare(
                "SELECT completed
                 FROM tasks
                 WHERE id = :id"
            );

            $stmt->execute([
                'id' => $id
            ]);

            $task = $stmt->fetch();

            if ($task) {

                if ($task['completed']) {

                    // Descompletar
                    $stmt = $pdo->prepare(
                        "UPDATE tasks
                         SET completed = 0,
                             completed_at = NULL,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id"
                    );
                } else {

                    // Completar
                    $stmt = $pdo->prepare(
                        "UPDATE tasks
                         SET completed = 1,
                             completed_at = CURRENT_TIMESTAMP,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id"
                    );
                }

                $stmt->execute([
                    'id' => $id
                ]);
            }
        }

        header('Location: index.php');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR TAREA
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        if ($id) {

            $stmt = $pdo->prepare(
                "DELETE FROM tasks
                 WHERE id = :id"
            );

            $stmt->execute([
                'id' => $id
            ]);
        }

        header('Location: index.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| OBTENER TAREAS PENDIENTES
|--------------------------------------------------------------------------
|
| 1. Las que tienen fecha límite primero.
| 2. La fecha límite más cercana primero.
| 3. Las que no tienen fecha límite al final.
|
*/

$stmt = $pdo->query(
    "SELECT *
     FROM tasks
     WHERE completed = 0
     ORDER BY
        due_at IS NULL,
        due_at ASC,
        created_at DESC"
);

$pendingTasks = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| OBTENER TAREAS COMPLETADAS
|--------------------------------------------------------------------------
|
| Las completadas más recientemente aparecen primero.
|
*/

$stmt = $pdo->query(
    "SELECT *
     FROM tasks
     WHERE completed = 1
     ORDER BY completed_at DESC"
);

$completedTasks = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Task Manager</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>


<body>

    <main>

        <h1>Task Manager</h1>


        <!-- =========================================================
             CREAR TAREA
        ========================================================== -->

        <section class="create-task">

            <h2>Crear tarea</h2>

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="create">


                <div>

                    <label for="title">
                        Título
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="255"
                        required>

                </div>


                <div>

                    <label for="description">
                        Descripción
                    </label>

                    <textarea
                        id="description"
                        name="description"></textarea>

                </div>


                <div>

                    <label for="due_at">
                        Fecha límite
                    </label>

                    <input
                        type="datetime-local"
                        id="due_at"
                        name="due_at">

                </div>


                <button type="submit">
                    Añadir tarea
                </button>

            </form>

        </section>


        <!-- =========================================================
             TAREAS PENDIENTES
        ========================================================== -->

        <section class="tasks">

            <h2>Tareas pendientes</h2>


            <?php if (empty($pendingTasks)): ?>

                <p>
                    No hay tareas pendientes.
                </p>

            <?php else: ?>


                <?php foreach ($pendingTasks as $task): ?>

                    <article class="task">

                        <h3>
                            <?= htmlspecialchars($task['title']) ?>
                        </h3>


                        <?php if (!empty($task['description'])): ?>

                            <p>
                                <?= htmlspecialchars($task['description']) ?>
                            </p>

                        <?php endif; ?>


                        <?php if ($task['due_at']): ?>

                            <p>
                                Fecha límite:
                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($task['due_at'])
                                ) ?>
                            </p>

                        <?php else: ?>

                            <p>
                                Sin fecha límite
                            </p>

                        <?php endif; ?>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="toggle">

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $task['id'] ?>">

                            <button type="submit">
                                Completar
                            </button>

                        </form>


                        <form
                            method="POST"
                            onsubmit="return confirm('¿Seguro que quieres eliminar esta tarea?');">

                            <input
                                type="hidden"
                                name="action"
                                value="delete">

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $task['id'] ?>">

                            <button type="submit">
                                Eliminar
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>


        <!-- =========================================================
             TAREAS COMPLETADAS
        ========================================================== -->

        <section class="tasks completed-tasks">

            <h2>Tareas completadas</h2>


            <?php if (empty($completedTasks)): ?>

                <p>
                    No hay tareas completadas.
                </p>

            <?php else: ?>


                <?php foreach ($completedTasks as $task): ?>

                    <article class="task completed">

                        <h3>
                            <?= htmlspecialchars($task['title']) ?>
                        </h3>


                        <?php if (!empty($task['description'])): ?>

                            <p>
                                <?= htmlspecialchars($task['description']) ?>
                            </p>

                        <?php endif; ?>


                        <p>
                            Completada el
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($task['completed_at'])
                            ) ?>
                        </p>


                        <?php if ($task['due_at']): ?>

                            <p>
                                Fecha límite:
                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($task['due_at'])
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="toggle">

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $task['id'] ?>">

                            <button type="submit">
                                Descompletar
                            </button>

                        </form>


                        <form
                            method="POST"
                            onsubmit="return confirm('¿Seguro que quieres eliminar esta tarea?');">

                            <input
                                type="hidden"
                                name="action"
                                value="delete">

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $task['id'] ?>">

                            <button type="submit">
                                Eliminar
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>


    <script src="assets/js/main.js"></script>

</body>

</html>