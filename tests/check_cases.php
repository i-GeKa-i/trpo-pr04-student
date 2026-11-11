<?php
// Проверка сценариев из dataset/form_cases.json: php tests/check_cases.php
declare(strict_types=1);

require_once __DIR__ . '/../src/functions.php';

$cases = json_decode((string) file_get_contents(__DIR__ . '/../dataset/form_cases.json'), true);
$failed = 0;

foreach ($cases as $case) {
    $errors = validateInput(['name' => $case['name'], 'experience' => $case['experience']]);
    $ok = (count($errors) === 0) === $case['valid'];
    $failed += $ok ? 0 : 1;
    printf("%s  %-15s ожидалось valid=%s, ошибок: %d\n", $ok ? 'OK  ' : 'FAIL', $case['case'], $case['valid'] ? 'true' : 'false', count($errors));
}

// Доп. проверки: пробелы вместо имени, неверный email, фильтр
$extra = [
    'пробелы вместо имени' => isset(validateInput(['name' => '   ', 'experience' => '3'])['name']),
    'неверный email' => isset(validateInput(['name' => 'Иван', 'email' => 'abc', 'experience' => '3'])['email']),
    'стаж не число' => isset(validateInput(['name' => 'Иван', 'experience' => 'abc'])['experience']),
    'фильтр "Разработка" даёт 2' => count(filterByDepartment(loadEmployees(), 'Разработка')) === 2,
    'пустой фильтр даёт всех (5)' => count(filterByDepartment(loadEmployees(), '')) === 5,
];
foreach ($extra as $title => $ok) {
    $failed += $ok ? 0 : 1;
    printf("%s  %s\n", $ok ? 'OK  ' : 'FAIL', $title);
}

exit($failed === 0 ? 0 : 1);
