<?php
// Nécessaire pour inclure l'environnement Dolibarr
$res = @include '../../main.inc.php';
if (! $res) $res = @include '../../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// Sécurité : vérifie que l'utilisateur est connecté
if (empty($user->id)) {
    http_response_code(401);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Définition du nom de l'extrafield à modifier
$extrafield_name = 'driver_closed'; // à adapter selon ton nom réel

// Récupération de l'action AJAX
$action = GETPOST('action', 'alpha');

if ($action === 'driver_closed') {
    // Chargement de l'objet utilisateur courant
    $u = new User($db);
    if ($u->fetch($user->id) > 0) {

        // Mise à jour de l'extrafield
        $u->array_options['options_' . $extrafield_name] = 1; // booléen à true
        $result = $u->insertExtraFields();

        if ($result > 0) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update extrafield']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
