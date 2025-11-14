<?php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to sanitize filename
function sanitize_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9-_.]/', '', $filename); // Allow dots for extension
    return $filename;
}

// Function to sanitize input string
function sanitize_input($input) {
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed!");
    }

    // Get form inputs
    $keyword_file = sanitize_input($_POST['keyword_file'] ?? '');
    $description_file = sanitize_input($_POST['description_file'] ?? '');
    $output_filename_input = sanitize_input($_POST['output_filename'] ?? '');

    // Define folder paths
    $keyword_folder = 'keyword/';
    $description_folder = 'descriptions/';
    $content_folder = 'content/';

    // Validate inputs
    if (empty($keyword_file) || empty($description_file) || empty($output_filename_input)) {
        $error = "All fields are required!";
    } else {
        // Sanitize and validate output filename
        $output_filename = sanitize_filename($output_filename_input);
        if (!str_ends_with($output_filename, '.json')) {
            $output_filename .= '.json';
        }

        // Check if file already exists
        $output_path = $content_folder . $output_filename;
        if (file_exists($output_path)) {
            $error = "File already exists! Please choose a different name.";
        } else {
            // Read keyword file
            $keyword_path = $keyword_folder . $keyword_file;
            if (!file_exists($keyword_path)) {
                $error = "Keyword file not found!";
            } else {
                $keywords = file($keyword_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                // Read description file
                $description_path = $description_folder . $description_file;
                if (!file_exists($description_path)) {
                    $error = "Description file not found!";
                } else {
                    $descriptions = file($description_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    // Create JSON content
                    $json_data = [];
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        if (!empty($keyword) && !empty($descriptions)) { // Ensure keyword and descriptions are not empty
                            $random_description = $descriptions[array_rand($descriptions)];
                            $json_data[$keyword] = trim($random_description);
                        }
                    }

                    // Create content folder if it doesn't exist
                    if (!is_dir($content_folder)) {
                        mkdir($content_folder, 0777, true);
                    }

                    // Save JSON to file
                    $json_string = json_encode($json_data, JSON_PRETTY_PRINT);
                    if (file_put_contents($output_path, $json_string)) {
                        $success = "JSON file created successfully: $output_filename";
                        $file_link = $output_path;
                    } else {
                        $error = "Error creating JSON file!";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html class="dark">
<head>
    <title>Create JSON Content</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-6 bg-gray-800 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 text-center">Spam KW Content</h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-500 text-white p-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
                <span id="copy-message" class="text-sm ml-2" style="display:none;">(Filename copied!)</span>
                <br>
                <a href="<?php echo htmlspecialchars($file_link); ?>" 
                   class="text-blue-300 hover:text-blue-400 underline" 
                   target="_blank">View File</a>
            </div>

            <script>
                (function() {
                    // Ambil nama file dari variabel PHP
                    const filenameToCopy = <?php echo json_encode($output_filename); ?>;
                    
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(filenameToCopy).then(function() {
                            // Tampilkan pesan sukses copy
                            document.getElementById('copy-message').style.display = 'inline';
                        }).catch(function(err) {
                            // Tampilkan error di console jika gagal
                            console.error('Could not copy text: ', err);
                        });
                    } else {
                        console.warn('Clipboard API not supported');
                    }
                })();
            </script>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div>
                <label class="block text-sm font-medium mb-1">Select Keyword File:</label>
                <select name="keyword_file" 
                        class="w-full bg-gray-700 border border-gray-600 rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="">Select file</option>
                    <?php
                    $keyword_files = glob('keyword/*.txt');
                    foreach ($keyword_files as $file) {
                        $filename = basename($file);
                        // MODIFIKASI: Tambahkan 'selected' jika nama file adalah 'default.txt'
                        $selected = ($filename === 'default.txt') ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($filename) . "' $selected>" . htmlspecialchars($filename) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Select Description File:</label>
                <select name="description_file" 
                        class="w-full bg-gray-700 border border-gray-600 rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    <option value="">Select file</option>
                    <?php
                    $description_files = glob('descriptions/*.txt');
                    foreach ($description_files as $file) {
                        $filename = basename($file);
                        // MODIFIKASI: Tambahkan 'selected' jika nama file adalah 'default.txt'
                        $selected = ($filename === 'default.txt') ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($filename) . "' $selected>" . htmlspecialchars($filename) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Output JSON Filename:</label>
                <input type="text" 
                       name="output_filename" 
                       placeholder="contentsample.json" 
                       class="w-full bg-gray-700 border border-gray-600 rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                Create JSON
            </button>
        </form>
    </div>
</body>
</html>
