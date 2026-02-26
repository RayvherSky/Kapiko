<?php
require_once 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('PHPInput_Resume_Format.docx');

    // 1. Handle Names and Dates
    $firstName = trim($_POST['FirstName']);
    $lastName = trim($_POST['LastName']);
    $fullName = $firstName . ' ' . $lastName;
    $templateProcessor->setValue('FullName', $fullName);

    $formattedDate = date('F d, Y', strtotime($_POST['BirthDate']));
    $templateProcessor->setValue('BirthDate', $formattedDate);

    $languagesArray = array_filter($_POST['Language']); 
    $languageString = implode(' / ', $languagesArray);
    $templateProcessor->setValue('Language', $languageString);

    // 2. Handle Dynamic Education Blocks
    
    // Tertiary Block
    if (!empty($_POST['CollegeName'])) {
        // Keep the block (1 clone)
        $templateProcessor->cloneBlock('block_tertiary', 1, true, false);
        $templateProcessor->setValue('CollegeName', $_POST['CollegeName']);
        // Format the date picker to just show the Year (e.g., 2024)
        $templateProcessor->setValue('CollegeStart', date('Y', strtotime($_POST['CollegeStart'])));
        $templateProcessor->setValue('CollegeEnd', date('Y', strtotime($_POST['CollegeEnd'])));
    } else {
        // Delete the block entirely (0 clones)
        $templateProcessor->cloneBlock('block_tertiary', 0, true, true);
    }

    // High School Block
    if (!empty($_POST['HighSchoolName'])) {
        $templateProcessor->cloneBlock('block_highschool', 1, true, false);
        $templateProcessor->setValue('HighSchoolName', $_POST['HighSchoolName']);
        $templateProcessor->setValue('HighSchoolStart', date('Y', strtotime($_POST['HighSchoolStart'])));
        $templateProcessor->setValue('HighSchoolEnd', date('Y', strtotime($_POST['HighSchoolEnd'])));
    } else {
        $templateProcessor->cloneBlock('block_highschool', 0, true, true);
    }

    // Elementary Block
    if (!empty($_POST['ElementaryName'])) {
        $templateProcessor->cloneBlock('block_elementary', 1, true, false);
        $templateProcessor->setValue('ElementaryName', $_POST['ElementaryName']);
        $templateProcessor->setValue('ElementaryStart', date('Y', strtotime($_POST['ElementaryStart'])));
        $templateProcessor->setValue('ElementaryEnd', date('Y', strtotime($_POST['ElementaryEnd'])));
    } else {
        $templateProcessor->cloneBlock('block_elementary', 0, true, true);
    }

    // 3. Handle the rest of the standard fields
    $fields = [
        'Address', 'Mobile', 'Email', 'Objective', 
        'Religion', 'BirthPlace', 'Height', 'Age', 
        'Weight', 'Sex', 'CivilStatus', 'EmergencyContactName', 
        'Nationality', 'EmergencyContact', 'WorkExperience', 'CharacterReference'
    ];

    foreach ($fields as $field) {
        $value = !empty($_POST[$field]) ? $_POST[$field] : ' ';
        $templateProcessor->setValue($field, $value);
    }

    // 4. Custom Filename
    $safeLastName = str_replace(' ', '_', $lastName);
    $safeFirstName = str_replace(' ', '_', $firstName);
    $dateCreated = date('Y-m-d'); 
    
    $fileName = 'outputs/' . $safeLastName . '_' . $safeFirstName . '_' . $dateCreated . '.docx';

    // 5. Save and Display
    $templateProcessor->saveAs($fileName);

    echo "<div style='font-family: Arial; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: green;'>Success!</h1>";
    echo "<h3>Resume generated for " . htmlspecialchars($fullName) . "</h3>";
    echo "<p>Saved as: <b>" . $fileName . "</b></p>";
    echo "<a href='index.html' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Make Another</a>";
    echo "</div>";

} else {
    echo "Error: Form not submitted.";
}
?>