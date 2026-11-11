<?php
declare(strict_types=1);

// Ошибки показываем только при локальной разработке (шаг 15)
if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/../src/functions.php';

header('Content-Type: text/html; charset=utf-8');

// Шаг 4: переменные разных типов и массив
$appTitle = 'Заявки сотрудников';      // string
$today = date('d.m.Y');                // string
$stages = ['Данные', 'Форма', 'Проверка']; // array

$employees = loadEmployees();
$departments = departmentsOf($employees);

// Фильтр по одному GET-параметру (шаг 13)
$filter = trim((string) ($_GET['department'] ?? ''));
$visible = filterByDepartment($employees, $filter);

// Обработка формы (шаги 8–10)
$values = ['name' => '', 'email' => '', 'department' => '', 'experience' => ''];
$errors = [];
$accepted = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $errors = validateInput($values, $departments);
    if (!$errors) {
        $accepted = $values + ['created' => date('Y-m-d H:i:s')];
        if (saveApplication($accepted)) {
            $values = ['name' => '', 'email' => '', 'department' => '', 'experience' => ''];
        } else {
            $errors['form'] = 'Не удалось сохранить заявку. Проверьте права на папку data';
            $accepted = null;
        }
    }
}
$applications = loadApplications();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appTitle) ?> — ПР №4</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="brand">Практическая работа 4</div>
    <span>ТРПО · v<?= e(APP_VERSION) ?> · <?= e($today) ?></span>
</header>

<main class="wrap">
    <section class="hero">
        <span class="pill">PHP без MVC</span>
        <h1>PHP. Основы языка, запросы и обработка HTML-форм</h1>
        <p class="muted">Версия приложения <?= e(APP_VERSION) ?>, сервер выполнил страницу <?= e($today) ?>.
            Данные берутся из <code>dataset/employees.csv</code>.
            <a href="#employees">К списку сотрудников</a></p>
    </section>

    <section class="grid" aria-label="Этапы работы">
        <?php foreach ($stages as $i => $stage): ?>
            <div class="card"><b><?= e($stage) ?></b><span class="pill">этап <?= $i + 1 ?></span></div>
        <?php endforeach; ?>
    </section>

    <section id="employees" class="panel">
        <h2>Сотрудники</h2>
        <form method="get" action="#employees" class="inline">
            <label for="f-dep">Отдел</label>
            <select id="f-dep" name="department">
                <option value="">Все отделы</option>
                <?php foreach ($departments as $dep): ?>
                    <option value="<?= e($dep) ?>" <?= $dep === $filter ? 'selected' : '' ?>><?= e($dep) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Показать</button>
        </form>

        <?php if (!$visible): ?>
            <p class="empty">Сотрудники не найдены<?= $filter !== '' ? ' в отделе «' . e($filter) . '»' : '' ?>.</p>
        <?php else: ?>
            <table>
                <tr><th>ID</th><th>ФИО</th><th>Отдел</th><th>Стаж, лет</th><th>Email</th></tr>
                <?php foreach ($visible as $emp): ?>
                    <tr>
                        <td><?= $emp['id'] ?></td>
                        <td><?= e($emp['name']) ?></td>
                        <td><span class="pill"><?= e($emp['department']) ?></span></td>
                        <td><?= $emp['experience'] ?></td>
                        <td><?= e($emp['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </section>

    <section id="request" class="panel">
        <h2>Заявка сотрудника</h2>

        <?php if ($accepted): ?>
            <p class="ok" role="status">Заявка принята: <?= e($accepted['name']) ?> (<?= e($accepted['department']) ?>).</p>
        <?php endif; ?>

        <?php if (isset($errors['form'])): ?>
            <div class="errors" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <form method="post" action="#request" novalidate>
            <label for="name">ФИО</label>
            <input id="name" name="name" value="<?= e($values['name']) ?>">
            <?php if (isset($errors['name'])): ?><small class="err"><?= e($errors['name']) ?></small><?php endif; ?>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e($values['email']) ?>">
            <?php if (isset($errors['email'])): ?><small class="err"><?= e($errors['email']) ?></small><?php endif; ?>

            <label for="department">Отдел</label>
            <select id="department" name="department">
                <?php foreach ($departments as $dep): ?>
                    <option value="<?= e($dep) ?>" <?= $dep === $values['department'] ? 'selected' : '' ?>><?= e($dep) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['department'])): ?><small class="err"><?= e($errors['department']) ?></small><?php endif; ?>

            <label for="experience">Стаж, лет (0–50)</label>
            <input id="experience" name="experience" value="<?= e($values['experience']) ?>">
            <?php if (isset($errors['experience'])): ?><small class="err"><?= e($errors['experience']) ?></small><?php endif; ?>

            <button type="submit">Отправить заявку</button>
        </form>
    </section>

    <section id="applications" class="panel">
        <h2>Принятые заявки</h2>
        <?php if (!$applications): ?>
            <p class="empty">Заявок пока нет.</p>
        <?php else: ?>
            <table>
                <tr><th>Дата</th><th>ФИО</th><th>Email</th><th>Отдел</th><th>Стаж</th></tr>
                <?php foreach (array_reverse($applications) as $app): ?>
                    <tr>
                        <td><?= e((string) ($app['created'] ?? '')) ?></td>
                        <td><?= e((string) ($app['name'] ?? '')) ?></td>
                        <td><?= e((string) ($app['email'] ?? '')) ?></td>
                        <td><?= e((string) ($app['department'] ?? '')) ?></td>
                        <td><?= e((string) ($app['experience'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
