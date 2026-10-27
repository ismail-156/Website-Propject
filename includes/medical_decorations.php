<?php
$decorationRole = $_SESSION['user_role'] ?? 'public';
$medicalArtwork = match ($decorationRole) {
    'parent' => [
        '3d_virus_protection_icon_a_shield_with_viral_cell_molecules_and_medical_pills___Premium_Photo-removebg-preview.png',
        '3D_Medical_Icon_Featuring_Syringe_and_DNA_Helix-removebg-preview.png',
        '3D_Icon_of_Botox_Injection_and_Vial_in_Isometric_View-removebg-preview.png',
        'Realistic_3D_Liver_Icon_in_Soft_Glow_and_Shadows_-_Woopicx-removebg-preview.png',
    ],
    'hospital' => [
        'Illustration_of_a_Laboratory_Test_Tube_with_Liquid-removebg-preview.png',
        '3D_Icon_of_Botox_Injection_and_Vial_in_Isometric_View-removebg-preview.png',
        '3D_Medical_Icon_Featuring_Syringe_and_DNA_Helix-removebg-preview.png',
        'Realistic_3D_Liver_Icon_in_Soft_Glow_and_Shadows_-_Woopicx-removebg-preview.png',
    ],
    'admin' => [
        '3D_Virus_Icon_with_Syringe_in_Isometric_View-removebg-preview.png',
        'Illustration_of_a_Laboratory_Test_Tube_with_Liquid-removebg-preview.png',
        '3d_virus_protection_icon_a_shield_with_viral_cell_molecules_and_medical_pills___Premium_Photo-removebg-preview.png',
        '3D_Medical_Icon_Featuring_Syringe_and_DNA_Helix-removebg-preview.png',
    ],
    default => [
        '3D_Virus_Icon_with_Syringe_in_Isometric_View-removebg-preview.png',
        '3d_virus_protection_icon_a_shield_with_viral_cell_molecules_and_medical_pills___Premium_Photo-removebg-preview.png',
        'Illustration_of_a_Laboratory_Test_Tube_with_Liquid-removebg-preview.png',
        '3D_Medical_Icon_Featuring_Syringe_and_DNA_Helix-removebg-preview.png',
    ],
};
$decorationSurface = in_array($decorationRole, ['parent', 'hospital', 'admin'], true)
    ? $decorationRole
    : 'public';
?>
<!-- Decorative medical illustrations stay outside the content and never intercept input. -->
<div class="medical-decorations medical-decorations--<?= htmlspecialchars($decorationSurface, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
    <?php foreach ($medicalArtwork as $index => $artwork): ?>
        <img
            class="medical-float medical-float--<?= $index + 1 ?>"
            src="<?= htmlspecialchars(appUrl('assets/images/medical/' . $artwork), ENT_QUOTES, 'UTF-8') ?>"
            alt=""
            width="112"
            height="112"
            loading="eager"
            fetchpriority="low"
            decoding="async">
    <?php endforeach; ?>
</div>
