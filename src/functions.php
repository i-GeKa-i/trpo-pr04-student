<?php
declare(strict_types=1);

const APP_VERSION = '1.0.0';
const EMPLOYEES_FILE = __DIR__ . '/../dataset/employees.csv';
const APPLICATIONS_FILE = __DIR__ . '/../data/applications.json';

/** Безопасный вывод строки в HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Преобразует одну строку CSV в структуру сотрудника. */
function parseEmployeeRow(array $row): array
{
    return [
        'id' => (int) $row[0],
        'name' => trim($row[1]),
        'department' => trim($row[2]),
        'experience' => (int) $row[3],
        'email' => trim($row[4]),
    ];
}

/** Читает employees.csv (разделитель «;») и возвращает массив сотрудников. */
function loadEmployees(string $path = EMPLOYEES_FILE): array
{
    $handle = @fopen($path, 'r');
    if ($handle === false) {
        return [];
    }
    $employees = [];
    $isHeader = true;
    while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
        if ($isHeader) { // первая строка — заголовок
            $isHeader = false;
            continue;
        }
        if (count($row) < 5) { // пустая или битая строка
            continue;
        }
        $employees[] = parseEmployeeRow($row);
    }
    fclose($handle);
    return $employees;
}

/** Список отделов без повторов (для фильтра и поля формы). */
function departmentsOf(array $employees): array
{
    $list = array_values(array_unique(array_column($employees, 'department')));
    sort($list);
    return $list;
}

/** Фильтр по отделу; пустое значение — все записи. */
function filterByDepartment(array $employees, string $department): array
{
    if ($department === '') {
        return $employees;
    }
    return array_values(array_filter(
        $employees,
        fn(array $emp): bool => $emp['department'] === $department
    ));
}

/**
 * Серверная валидация формы заявки.
 * Принимает «сырые» значения и возвращает массив ошибок [поле => сообщение].
 * Опционально проверяет отдел по списку допустимых.
 */
function validateInput(array $input, array $allowedDepartments = []): array
{
    $errors = [];
    $name = trim((string) ($input['name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $department = trim((string) ($input['department'] ?? ''));
    $experience = $input['experience'] ?? '';

    if ($name === '') {
        $errors['name'] = 'Укажите ФИО';
    } elseif (($len = preg_match_all('/./us', $name)) < 2 || $len > 100) { // без зависимости от mbstring
        $errors['name'] = 'ФИО должно содержать от 2 до 100 символов';
    }

    // email проверяется только если он передан в форме (в form_cases.json его нет)
    if (array_key_exists('email', $input) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }

    if (array_key_exists('department', $input) && ($department === '' || ($allowedDepartments && !in_array($department, $allowedDepartments, true)))) {
        $errors['department'] = 'Выберите отдел из списка';
    }

    $expStr = trim((string) $experience);
    if ($expStr === '' || filter_var($expStr, FILTER_VALIDATE_INT) === false) {
        $errors['experience'] = 'Стаж должен быть целым числом';
    } elseif ((int) $expStr < 0 || (int) $expStr > 50) {
        $errors['experience'] = 'Стаж должен быть от 0 до 50 лет';
    }

    return $errors;
}

/** Сохраняет принятую заявку в data/applications.json. */
function saveApplication(array $application, string $path = APPLICATIONS_FILE): bool
{
    $all = [];
    if (is_file($path)) {
        $all = json_decode((string) file_get_contents($path), true) ?: [];
    }
    $all[] = $application;
    return file_put_contents($path, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

/** Читает ранее сохранённые заявки. */
function loadApplications(string $path = APPLICATIONS_FILE): array
{
    if (!is_file($path)) {
        return [];
    }
    return json_decode((string) file_get_contents($path), true) ?: [];
}
