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
    | COMPLETAR / DESCOMPLETAR
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

                    $stmt = $pdo->prepare(
                        "UPDATE tasks
                         SET completed = 0,
                             completed_at = NULL,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id"
                    );
                } else {

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
| Las tareas con fecha aparecen primero y se ordenan
| según cuál vence antes.
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
| Las últimas completadas aparecen primero.
|
*/

$stmt = $pdo->query(
    "SELECT *
     FROM tasks
     WHERE completed = 1
     ORDER BY completed_at DESC, updated_at DESC"
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
        <button
            type="button"
            id="theme-toggle"
            class="theme-toggle"
            aria-label="Cambiar tema">
            🌙 Dark mode
        </button>

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
                        placeholder="¿Qué necesitas hacer?"
                        required>

                </div>


                <div>

                    <label for="description">
                        Descripción
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Añade una descripción..."></textarea>

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

                    <?php

                    /*
                 * Determinar el estado visual de la tarea.
                 *
                 * Azul:
                 * Sin fecha o más de 24 horas.
                 *
                 * Amarillo:
                 * Menos de 24 horas para vencer.
                 *
                 * Rojo:
                 * Fecha límite vencida.
                 */

                    $taskClass = 'task--normal';

                    if (!empty($task['due_at'])) {

                        $now = new DateTime();
                        $dueDate = new DateTime($task['due_at']);

                        $secondsRemaining =
                            $dueDate->getTimestamp() - $now->getTimestamp();


                        if ($secondsRemaining <= 0) {

                            $taskClass = 'task--overdue';
                        } elseif ($secondsRemaining <= 86400) {

                            $taskClass = 'task--warning';
                        }
                    }

                    ?>

                    <article class="task <?= $taskClass ?>">

                        <h3>
                            <?= htmlspecialchars($task['title']) ?>
                        </h3>


                        <?php if (!empty($task['description'])): ?>

                            <p>
                                <?= nl2br(
                                    htmlspecialchars($task['description'])
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <?php if (!empty($task['due_at'])): ?>

                            <p class="task-date">

                                Fecha límite:

                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($task['due_at'])
                                ) ?>

                            </p>

                        <?php else: ?>

                            <p class="task-date">
                                Sin fecha límite
                            </p>

                        <?php endif; ?>


                        <div class="task-actions">

                            <!-- Completar -->

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="toggle">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $task['id'] ?>">

                                <button
                                    type="submit"
                                    class="btn-complete">
                                    Completar
                                </button>

                            </form>


                            <!-- Eliminar -->

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

                                <button
                                    type="submit"
                                    class="btn-delete">
                                    Eliminar
                                </button>

                            </form>

                        </div>

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
                                <?= nl2br(
                                    htmlspecialchars($task['description'])
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <p class="task-date">

                            <?php if (!empty($task['completed_at'])): ?>

                                Completada el

                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($task['completed_at'])
                                ) ?>

                            <?php else: ?>

                                Completada

                            <?php endif; ?>

                        </p>


                        <?php if (!empty($task['due_at'])): ?>

                            <p class="task-date">

                                Fecha límite:

                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($task['due_at'])
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <div class="task-actions">

                            <!-- Descompletar -->

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="toggle">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $task['id'] ?>">

                                <button
                                    type="submit"
                                    class="btn-complete">
                                    Descompletar
                                </button>

                            </form>


                            <!-- Eliminar -->

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

                                <button
                                    type="submit"
                                    class="btn-delete">
                                    Eliminar
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>


    <script src="assets/js/main.js"></script>

</body>

</html>