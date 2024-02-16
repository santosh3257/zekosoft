<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class SyncUserPermissions extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync-user-permissions {all?} {fresh?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync User Permissions';

    public function handle()
    {
        $output = new ConsoleOutput();

        $unsyncedUsers = User::with('roles', 'role')
            ->where('permission_sync', 0)
            ->when($this->argument('all'), function ($query) {
                return $query->get();
            }, function ($query) {
                return $query->limit(10)->get();
            });

        if ($unsyncedUsers->isEmpty()) {
            $output->writeln('<info>All user permissions are synced</info>');

            return true;
        }

        $total = $unsyncedUsers->count();

        $unsyncedUsers->each(function ($user, $key) use ($total) {
            $remaining = $total - $key;
            $userRole = $user->roles->pluck('name')->toArray();

            if (!in_array('admin', $userRole) && count($userRole) == 1) {
                $this->assignPermissions($user, $remaining);
            }
            elseif (in_array('admin', $userRole)) {
                $this->assignPermissions($user, $remaining);
            }

            $user->permission_sync = 1;
            $user->saveQuietly();
        });
    }

    private function assignPermissions($user, $remaining)
    {
        $output = new ConsoleOutput();
        //phpcs:ignore
        $output->writeln('<info>Remaining: ' . $remaining . ' Syncing permission started for ' . $user->name . '</info>');
        $roleId = $user->role[0]->role_id;

        if ($this->argument('fresh')) {
            $user->insertUserRolePermission($roleId);
        }
        else {
            $user->assignUserRolePermission($roleId);
        }

        //phpcs:ignore
        $output->writeln('<info>Remaining: ' . $remaining . ' Syncing permission ended for ' . $user->name . '</info>');
    }

}
