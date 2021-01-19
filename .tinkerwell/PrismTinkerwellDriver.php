<?php

class PrismTinkerwellDriver extends TinkerwellDriver
{
    /**
     * Determine if the driver can be used with the selected project path.
     * You most likely want to check the existence of project / framework specific files.
     *
     * @param  string $projectPath
     * @return  bool
     */
    public function canBootstrap($projectPath)
    {
        return true;
    }

    /**
     * Bootstrap the application so that any executed can access the application in your desired state.
     *
     * @param  string $projectPath
     */
    public function bootstrap($projectPath)
    {
        require $projectPath . '/vendor/autoload.php';
    }
}