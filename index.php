<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_host = 'localhost';
$db_user = 'u82194';
$db_pass = '8381502';
$db_name = 'u82194';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $full_name = isset($_POST['fio']) ? trim($_POST['fio']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $birth_date = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $selected_languages = isset($_POST['language']) ? $_POST['language'] : [];
    $biography = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $contract_accepted = isset($_POST['agreement']) ? true : false;
    
    if (empty($full_name)) {
    $errors['fio'] = 'ФИО обязательно для заполнения';
} elseif (strlen($full_name) > 150) {
    $errors['fio'] = 'ФИО не может быть длиннее 150 символов';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $full_name)) {
    $errors['fio'] = 'ФИО может содержать только буквы, пробелы и дефисы';
}
    
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен для заполнения';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный формат email';
    }
    
    if (empty($birth_date)) {
        $errors['birthdate'] = 'Дата рождения обязательна';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $errors['birthdate'] = 'Неверный формат даты';
    }
    
    $allowed_genders = ['male', 'female'];
    if (empty($gender)) {
        $errors['gender'] = 'Выберите пол';
    } elseif (!in_array($gender, $allowed_genders)) {
        $errors['gender'] = 'Некорректное значение пола';
    }
    
    if (empty($selected_languages)) {
        $errors['language'] = 'Выберите хотя бы один язык программирования';
    }
    
    if (!$contract_accepted) {
        $errors['agreement'] = 'Необходимо подтвердить ознакомление с контрактом';
    }
    
    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $pdo->beginTransaction();
            
            $sql = "INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_accepted) 
                    VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':email' => $email,
                ':birth_date' => $birth_date,
                ':gender' => $gender,
                ':biography' => $biography,
                ':contract_accepted' => $contract_accepted ? 1 : 0
            ]);
            
            $application_id = $pdo->lastInsertId();
            
            $placeholders = str_repeat('?,', count($selected_languages) - 1) . '?';
            $sql = "SELECT id, name FROM programming_languages WHERE name IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($selected_languages);
            $languages = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $sql = "INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($selected_languages as $lang_name) {
                if (isset($languages[$lang_name])) {
                    $stmt->execute([$application_id, $languages[$lang_name]]);
                }
            }
            
            $pdo->commit();
            
            $success_message = 'Данные успешно сохранены!';
            
            $_POST = [];
            
        } catch (PDOException $e) {
            if ($pdo) {
                $pdo->rollBack();
            }
            $errors['database'] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма с CSS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 5px;
            padding: 5px;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .field-error {
            border-color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Форма</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors['database'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($errors['database']) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="fio">ФИО:</label>
                <input type="text" id="fio" name="fio" 
                       value="<?= isset($_POST['fio']) ? htmlspecialchars($_POST['fio']) : '' ?>"
                       class="<?= isset($errors['fio']) ? 'field-error' : '' ?>">
                <?php if (isset($errors['fio'])): ?>
                    <div class="error-message"><?= $errors['fio'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                       class="<?= isset($errors['phone']) ? 'field-error' : '' ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="error-message"><?= $errors['phone'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       class="<?= isset($errors['email']) ? 'field-error' : '' ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?= $errors['email'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <input type="date" id="birthdate" name="birthdate" 
                       value="<?= isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : '' ?>"
                       class="<?= isset($errors['birthdate']) ? 'field-error' : '' ?>">
                <?php if (isset($errors['birthdate'])): ?>
                    <div class="error-message"><?= $errors['birthdate'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Пол:</label>
                <div class="radio-group">
                    <input type="radio" id="male" name="gender" value="male"
                           <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'checked' : '' ?>>
                    <label for="male">Мужской</label>
                    
                    <input type="radio" id="female" name="gender" value="female"
                           <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'checked' : '' ?>>
                    <label for="female">Женский</label>
                </div>
                <?php if (isset($errors['gender'])): ?>
                    <div class="error-message"><?= $errors['gender'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="language">Любимый язык программирования:</label>
                <select id="language" name="language[]" multiple size="5"
                        class="<?= isset($errors['language']) ? 'field-error' : '' ?>">
                    <?php
                    $languages_list = [
                        'pascal' => 'Pascal',
                        'c' => 'C',
                        'cpp' => 'C++',
                        'javascript' => 'JavaScript',
                        'php' => 'PHP',
                        'python' => 'Python',
                        'java' => 'Java',
                        'haskell' => 'Haskell',
                        'clojure' => 'Clojure',
                        'prolog' => 'Prolog',
                        'scala' => 'Scala'
                    ];
                    $selected_langs = isset($_POST['language']) ? (array)$_POST['language'] : [];
                    foreach ($languages_list as $value => $label):
                        $selected = in_array($value, $selected_langs) ? 'selected' : '';
                    ?>
                        <option value="<?= $value ?>" <?= $selected ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['language'])): ?>
                    <div class="error-message"><?= $errors['language'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="bio">Биография:</label>
                <textarea id="bio" name="bio" rows="5"
                          class="<?= isset($errors['bio']) ? 'field-error' : '' ?>"><?= isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : '' ?></textarea>
                <?php if (isset($errors['bio'])): ?>
                    <div class="error-message"><?= $errors['bio'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group checkbox-wrapper">
                <input type="checkbox" id="agreement" name="agreement" value="1"
                       <?= isset($_POST['agreement']) ? 'checked' : '' ?>
                       class="<?= isset($errors['agreement']) ? 'field-error' : '' ?>">
                <label for="agreement">С контрактом ознакомлен(а)</label>
                <?php if (isset($errors['agreement'])): ?>
                    <div class="error-message"><?= $errors['agreement'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit">Сохранить</button>
            </div>
        </form>
    </div>
</body>
</html>