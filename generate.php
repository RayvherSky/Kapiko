<?php
require_once 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('PHPInput_Resume_Format.docx');

    // 1. Handle Special Fields First
    
    // Combine First and Last Name for the document tag
    $firstName = trim($_POST['FirstName']);
    $lastName = trim($_POST['LastName']);
    $fullName = $firstName . ' ' . $lastName;
    $templateProcessor->setValue('FullName', $fullName);

    // Format the Birth Date from YYYY-MM-DD to Month DD, YYYY
    $formattedDate = date('F d, Y', strtotime($_POST['BirthDate']));
    $templateProcessor->setValue('BirthDate', $formattedDate);

    // Stitch the Language array together with " / "
    $languagesArray = array_filter($_POST['Language']); // array_filter removes empty blank fields
    $languageString = implode(' / ', $languagesArray);
    $templateProcessor->setValue('Language', $languageString);


    // 2. Handle the rest of the standard fields
    // (Removed FullName, BirthDate, and Language from this list since we handled them above)
    $fields = [
        'Address', 'Mobile', 'Email', 'Objective', 
        'Religion', 'BirthPlace', 'Height', 'Age', 
        'Weight', 'Sex', 'CivilStatus', 'EmergencyContactName', 
        'Nationality', 'EmergencyContact', 'CollegeName', 'CollegeYear', 
        'HighSchoolName', 'HighSchoolYear', 'ElementaryName', 'ElementaryYear', 
        'WorkExperience', 'CharacterReference'
    ];

    foreach ($fields as $field) {
        $value = !empty($_POST[$field]) ? $_POST[$field] : ' ';
        $templateProcessor->setValue($field, $value);
    }

    // 3. Generate the Custom Filename: Lastname_FirstName_DateCreated.docx
    // Replace any spaces in the names with underscores just to be safe
    $safeLastName = str_replace(' ', '_', $lastName);
    $safeFirstName = str_replace(' ', '_', $firstName);
    $dateCreated = date('Y-m-d'); // Formats as 2026-02-26
    
    $fileName = 'outputs/' . $safeLastName . '_' . $safeFirstName . '_' . $dateCreated . '.docx';

    // 4. Save it
    $templateProcessor->saveAs($fileName);

    // 5. Display Success
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