<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checking for Dependency</title>
    <style>
        body {
            font-family: Inter;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #FFFFFF;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            gap: 16px;
        }

        .header {
            background-color: #fff;
            color: gray;
            text-align: left;
            font-size: 20px;
            font-weight: 700;
            line-height: 24.2px;
            padding: 16px;
            border-bottom: 1px solid #D9D9D9;
        }

        .output {
            padding: 20px;
            background-color: #000;
            color: #0f0;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-y: auto;
            max-height: 400px;
            white-space: pre-wrap;
        }

        .output-container {
            padding: 16px;
        }

        .footer {
            margin-top: 15px;
            display: flex !important;
            justify-content: end;
            align-items: center;
            gap:10px;
        }

        .footer a {
            color: #FFFFFF;
            border: 1px solid #6D28D8;
            text-decoration: none;
            font-size: 14px;
            background: #7C3AEC;
            padding: 6px 12px 6px 12px;
            gap: 4px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            line-height: 24px;
            text-underline-position: from-font;
            text-decoration-skip-ink: none;
        }

        .footer .error{
            color: #e24d4d
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Checking for Dependency</div>
        <div class="output-container">
            <div class="output">
                <?php
                    ob_start();

                echo '<pre>';

                $installationSuccessful = true;

                $requiredPhpVersion = '8.2';
                $requiredMySqlVersion = '8.0';

                // Function to execute and output command results
                function executeCommand($command, &$installationSuccessful)
                {
                    echo "Executing: $command\n";
                    flush();
                    ob_flush();

                    $output = [];
                    $returnVar = null;
                    exec($command, $output, $returnVar);

                    foreach ($output as $line) {
                        echo $line."\n";
                        flush();
                        ob_flush();
                    }

                    if ($returnVar !== 0) {
                        $installationSuccessful = false;
                        echo "ERROR: Command failed with exit code $returnVar.\n";

                        return false;
                    }

                    return implode("\n", $output);
                }

                // Check PHP version
                $phpVersion = phpversion();
                echo "PHP Version: $phpVersion\n";
                if (version_compare($phpVersion, $requiredPhpVersion, '<')) {
                    echo "ERROR: PHP version must be >= $requiredPhpVersion.\n";
                    exit(1);
                }

                echo '<br>';

                $extensions = [
                    'ctype',
                    'curl',
                    'dom',
                    'fileinfo',
                    'filter',
                    'gd',
                    'hash',
                    'intl',
                    'json',
                    'mbstring',
                    'openssl',
                    'pcre',
                    'pdo_mysql',
                    'simplexml',
                ];

                $enabledExtension = true;

                foreach ($extensions as $extension) {
                    if (extension_loaded($extension)) {
                        echo "$extension extension is enabled.<br>";
                    } else {
                        $enabledExtension = false;
                        echo "$extension extension is not enabled.<br>";
                    }
                }

                if (! $enabledExtension) {
                    $installationSuccessful = false;
                    echo "ERROR: Required extensions are not enabled. Please enable them and try again.\n";
                    exit(1);
                }

                echo '<br>';

                // Check MySQL version
                $mysqlVersionOutput = executeCommand('mysql --version', $installationSuccessful);
                if ($mysqlVersionOutput) {
                    preg_match('/Ver ([0-9.]+)/', $mysqlVersionOutput, $matches);
                    $mysqlVersion = $matches[1] ?? null;
                    if ($mysqlVersion && version_compare($mysqlVersion, $requiredMySqlVersion, '<')) {
                        $installationSuccessful = false;
                        echo "ERROR: MySQL version must be >= $requiredMySqlVersion.\n";
                        exit(1);
                    }
                } else {
                    $installationSuccessful = false;
                    echo "ERROR: MySQL is not installed or not accessible.\n";
                    exit(1);
                }

                echo '<br>';

                function installProjectDepedencies(&$installationSuccessful)
                {
                    $composerHome = realpath(__DIR__.'/../bin/composer/composer.phar');
                    $workingDirectory = realpath(__DIR__.'/..');

                    // Set environment variable
                    putenv("COMPOSER_HOME=$composerHome");

                    // Define multiple commands
                    $commands = [
                        "php $composerHome install --no-ansi --working-dir=$workingDirectory",
                    ];

                    if (file_exists("$workingDirectory/.env.example")) {
                        $commands[] = "php -r \"copy('$workingDirectory/.env.example', '$workingDirectory/.env');\"";
                    }

                    $commands[] = "php $workingDirectory/artisan key:generate";

                    foreach ($commands as $command) {
                        echo "Executing: $command\n";
                        flush();
                        ob_flush();

                        $descriptorspec = [
                            1 => ['pipe', 'w'], // stdout
                            2 => ['pipe', 'w'], // stderr
                        ];

                        $process = proc_open($command, $descriptorspec, $pipes);

                        if (is_resource($process)) {
                            // Read stdout
                            while ($line = fgets($pipes[1])) {
                                echo $line;
                                flush();
                                ob_flush();
                            }

                            // Read stderr
                            while ($error = fgets($pipes[2])) {
                                echo $error;
                                flush();
                                ob_flush();
                            }

                            // Close pipes
                            fclose($pipes[1]);
                            fclose($pipes[2]);

                            // Get the exit code
                            $return_value = proc_close($process);

                            if ($return_value !== 0) {
                                echo "\nCommand failed with exit code: $return_value.\n";
                                $installationSuccessful = false; // Mark failure
                                break; // Stop execution on failure
                            }

                            echo "\nCommand finished successfully with exit code: $return_value.\n";
                        } else {
                            echo "Failed to execute the command: $command\n";
                            $installationSuccessful = false; // Mark failure
                            break; // Stop execution if process creation fails
                        }
                    }

                    echo "\nAll commands executed.\n";
                    echo '</pre>';
                }

                installProjectDepedencies($installationSuccessful);

                ob_end_flush();
                ?>
            </div>

            <div class="footer">
                <?php
                if ($installationSuccessful) {
                    echo '<a href="install">Continue</a>';
                } else {
                    echo '<p class="error"> Installation Failed. Please check output above and try again. </p>';
                    echo '<a href="install.php">Try Again</a>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
