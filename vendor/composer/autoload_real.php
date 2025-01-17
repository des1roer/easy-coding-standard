<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitc718493e227af7b431a3a1eb1c6dc09a
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitc718493e227af7b431a3a1eb1c6dc09a', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitc718493e227af7b431a3a1eb1c6dc09a', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitc718493e227af7b431a3a1eb1c6dc09a::getInitializer($loader));

        $loader->setClassMapAuthoritative(true);
        $loader->register(true);

        $includeFiles = \Composer\Autoload\ComposerStaticInitc718493e227af7b431a3a1eb1c6dc09a::$files;
        foreach ($includeFiles as $fileIdentifier => $file) {
            composerRequirec718493e227af7b431a3a1eb1c6dc09a($fileIdentifier, $file);
        }

        return $loader;
    }
}

/**
 * @param string $fileIdentifier
 * @param string $file
 * @return void
 */
function composerRequirec718493e227af7b431a3a1eb1c6dc09a($fileIdentifier, $file)
{
    if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
        $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;

        require $file;
    }
}
