<?php
require_once 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('PHPInput_Resume_Format.docx');

   // 1. Handle Profile Picture Upload
    if (isset($_FILES['ProfilePicture']) && $_FILES['ProfilePicture']['error'] === UPLOAD_ERR_OK) {
        
        // Auto-create an 'uploads' directory if it doesn't exist yet
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $tmpName = $_FILES['ProfilePicture']['tmp_name'];
        // Generate safe unique name for the image
        $imageName = time() . '_' . basename($_FILES['ProfilePicture']['name']);
        
        // Route it to the new 'uploads' folder instead of 'outputs'
        $imagePath = 'uploads/' . $imageName;
        
        move_uploaded_file($tmpName, $imagePath);
        
        // Inject image into Word doc
        $templateProcessor->setImageValue('ProfilePicture', [
            'path' => $imagePath,
            'width' => 192, // Adjust this if you need it slightly bigger/smaller
            'height' => 192,
            'ratio' => false
        ]);
    } else {
        // No picture uploaded. Erase the text tag.
        $templateProcessor->setValue('ProfilePicture', ' ');
    }

    // 2. Handle Names and Dates
    $firstName = trim($_POST['FirstName']);
    $lastName = trim($_POST['LastName']);
    $fullName = $firstName . ' ' . $lastName;
    $templateProcessor->setValue('FullName', $fullName);

    $formattedDate = date('F d, Y', strtotime($_POST['BirthDate']));
    $templateProcessor->setValue('BirthDate', $formattedDate);

    $languagesArray = array_filter($_POST['Language'] ?? []); 
    $languageString = implode(' / ', $languagesArray);
    $templateProcessor->setValue('Language', $languageString);

    // 3. Handle Pickers & Dropdowns
    $heightString = $_POST['HeightFeet'] . "' " . $_POST['HeightInches'] . '"';
    $templateProcessor->setValue('Height', $heightString);

    $weightString = $_POST['WeightNum'] . ' kgs';
    $templateProcessor->setValue('Weight', $weightString);

    // Format Skills list (puts a bullet point and tab between each chosen skill)
    $skillsArray = $_POST['Skills'] ?? [];
    if (!empty($skillsArray)) {
        $skillsFormatted = '• ' . implode('          • ', $skillsArray);
        $templateProcessor->setValue('SkillsList', $skillsFormatted);
    } else {
        $templateProcessor->setValue('SkillsList', ' ');
    }

    // 4. Handle Dynamic Education Blocks
    if (!empty($_POST['CollegeName'])) {
        $templateProcessor->cloneBlock('block_tertiary', 1, true, false);
        $templateProcessor->setValue('CollegeName', $_POST['CollegeName']);
        $templateProcessor->setValue('CollegeStart', date('Y', strtotime($_POST['CollegeStart'])));
        $templateProcessor->setValue('CollegeEnd', date('Y', strtotime($_POST['CollegeEnd'])));
    } else {
        $templateProcessor->cloneBlock('block_tertiary', 0, true, true);
    }

    if (!empty($_POST['HighSchoolName'])) {
        $templateProcessor->cloneBlock('block_highschool', 1, true, false);
        $templateProcessor->setValue('HighSchoolName', $_POST['HighSchoolName']);
        $templateProcessor->setValue('HighSchoolStart', date('Y', strtotime($_POST['HighSchoolStart'])));
        $templateProcessor->setValue('HighSchoolEnd', date('Y', strtotime($_POST['HighSchoolEnd'])));
    } else {
        $templateProcessor->cloneBlock('block_highschool', 0, true, true);
    }

    if (!empty($_POST['ElementaryName'])) {
        $templateProcessor->cloneBlock('block_elementary', 1, true, false);
        $templateProcessor->setValue('ElementaryName', $_POST['ElementaryName']);
        $templateProcessor->setValue('ElementaryStart', date('Y', strtotime($_POST['ElementaryStart'])));
        $templateProcessor->setValue('ElementaryEnd', date('Y', strtotime($_POST['ElementaryEnd'])));
    } else {
        $templateProcessor->cloneBlock('block_elementary', 0, true, true);
    }

    // 5. Handle Work Experience Arrays
    $jobPositions = $_POST['JobPosition'] ?? [];
    $companyNames = $_POST['CompanyName'] ?? [];
    $jobStarts = $_POST['JobStart'] ?? [];
    $jobEnds = $_POST['JobEnd'] ?? [];
    
    $validJobs = [];
    for ($i = 0; $i < count($jobPositions); $i++) {
        if (!empty(trim($jobPositions[$i]))) {
            $start = !empty($jobStarts[$i]) ? date('F Y', strtotime($jobStarts[$i])) : '';
            $end = !empty($jobEnds[$i]) ? date('F Y', strtotime($jobEnds[$i])) : 'Present';
            $validJobs[] = ['JobPosition' => trim($jobPositions[$i]), 'CompanyName' => trim($companyNames[$i]), 'JobStart' => $start, 'JobEnd' => $end];
        }
    }

    $jobClones = count($validJobs);
    if ($jobClones > 0) {
        $templateProcessor->cloneBlock('block_work', $jobClones, true, true);
        $index = 1;
        foreach ($validJobs as $job) {
            $templateProcessor->setValue('JobPosition#' . $index, $job['JobPosition']);
            $templateProcessor->setValue('CompanyName#' . $index, $job['CompanyName']);
            $templateProcessor->setValue('JobStart#' . $index, $job['JobStart']);
            $templateProcessor->setValue('JobEnd#' . $index, $job['JobEnd']);
            $index++;
        }
    } else {
        $templateProcessor->cloneBlock('block_work', 0, true, true);
    }

    // 6. Handle Character Reference Arrays
    $refNames = $_POST['RefName'] ?? [];
    $refPositions = $_POST['RefPosition'] ?? [];
    $refContacts = $_POST['RefContact'] ?? [];

    $validRefs = [];
    for ($i = 0; $i < count($refNames); $i++) {
        if (!empty(trim($refNames[$i]))) {
            $validRefs[] = ['RefName' => trim($refNames[$i]), 'RefPosition' => trim($refPositions[$i]), 'RefContact' => trim($refContacts[$i])];
        }
    }

    $refClones = count($validRefs);
    if ($refClones > 0) {
        $templateProcessor->cloneBlock('block_reference', $refClones, true, true);
        $index = 1;
        foreach ($validRefs as $ref) {
            $templateProcessor->setValue('RefName#' . $index, $ref['RefName']);
            $templateProcessor->setValue('RefPosition#' . $index, $ref['RefPosition']);
            $templateProcessor->setValue('RefContact#' . $index, $ref['RefContact']);
            $index++;
        }
    } else {
        $templateProcessor->cloneBlock('block_reference', 0, true, true);
    }

    // 7. Handle the rest of the standard fields
    $fields = ['Address', 'Mobile', 'Email', 'Objective', 'Religion', 'BirthPlace', 'Age', 'Sex', 'CivilStatus', 'EmergencyContactName', 'Nationality', 'EmergencyContact'];
    foreach ($fields as $field) {
        $value = !empty($_POST[$field]) ? $_POST[$field] : ' ';
        $templateProcessor->setValue($field, $value);
    }

    // 8. Custom Filename
    $safeLastName = str_replace(' ', '_', $lastName);
    $safeFirstName = str_replace(' ', '_', $firstName);
    $dateCreated = date('Y-m-d'); 
    
    $fileName = 'outputs/' . $safeLastName . '_' . $safeFirstName . '_' . $dateCreated . '.docx';

    // 9. Save and Display
    $templateProcessor->saveAs($fileName);

    // Clean, customer-facing success screen
    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 100px; padding: 20px;'>";
    echo "<h1 style='color: #198754; font-size: 3.5rem; font-weight: bold; margin-bottom: 20px;'>Success!</h1>";
    echo "<h3 style='color: #333; font-weight: normal;'>Your input has successfully been saved.</h3>";
    echo "<p style='color: #555; font-size: 1.2rem; margin-top: 15px;'>Please inform the store owner for the printing.</p>";
    echo "</div>";

} else {
    echo "Error: Form not submitted.";
}
?>