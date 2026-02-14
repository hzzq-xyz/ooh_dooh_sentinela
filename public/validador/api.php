<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite acesso externo

// --- CONFIGURAÇÕES ---
// Aponta para o próprio servidor onde o Laravel está rodando
$laravelBaseUrl = "https://" . $_SERVER['HTTP_HOST']; 
$laravelApiUrl = $laravelBaseUrl . '/api/paineis/configuracoes';
$laravelWebhookUrl = $laravelBaseUrl . '/api/webhook/media-receiver';

$historyFile = 'history.json';
$uploadDir = 'uploads/';

if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'list_configs':
        // Busca do Laravel via CURL
        $ch = curl_init($laravelApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo ($httpCode === 200 && $response) ? $response : json_encode([]);
        break;

    case 'upload':
        // Lógica de Upload original
        if (!isset($_FILES['mediaFile']) || $_FILES['mediaFile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Erro no upload.']);
            break;
        }

        $file = $_FILES['mediaFile'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
        
        $fileId = date('dmYHis'); 
        $newName = $fileId . '_' . $cleanName . '.' . $ext;
        
        if(move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            // Atualiza histórico local
            $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
            if(count($history) > 50) array_pop($history); 
            
            $entry = [
                'id' => $fileId, 
                'file' => $newName, 
                'name' => $file['name'], 
                'config_used' => $_POST['config_name'], 
                'date' => date('d/m/Y H:i')
            ];
            array_unshift($history, $entry);
            file_put_contents($historyFile, json_encode($history));

            // URL Final do Link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $playerUrl = "$protocol://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/view.php?v=" . $newName;

            // Avisa o Laravel (Webhook)
            $ch = curl_init($laravelWebhookUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'event' => 'media.validated',
                'slot_id' => $fileId,
                'file_url' => $playerUrl,
                'panel_config' => $_POST['config_name']
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);

            echo json_encode(['success' => true, 'url' => "view.php?v=" . $newName]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao salvar.']);
        }
        break;
}