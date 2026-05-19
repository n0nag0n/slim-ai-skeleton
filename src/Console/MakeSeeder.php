<?php

declare(strict_types=1);

namespace App\Console;

use DI\Container;

class MakeSeeder implements CommandInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(array $args, Container $container): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "Usage: php console make:seeder <Name>\n";
            return 1;
        }

        $root = dirname(__DIR__, 2);
        $seedsDir = $root . '/database/seeds';

        if (!is_dir($seedsDir)) {
            mkdir($seedsDir, 0755, true);
        }

        $seederPath = $seedsDir . '/' . $name . 'Seeder.php';
        $seederStub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use Doctrine\DBAL\Connection;

class {$name}Seeder implements SeederInterface
{
    public function run(Connection \$conn): void
    {
        // \$conn->insert('table_name', ['column' => 'value']);
    }
}

PHP;
        file_put_contents($seederPath, $seederStub);
        echo "Created: database/seeds/{$name}Seeder.php\n";

        echo "\nNext: Edit database/seeds/{$name}Seeder.php and add your seed data.\n";
        echo "Then run: php console db:seed\n";

        return 0;
    }
}
