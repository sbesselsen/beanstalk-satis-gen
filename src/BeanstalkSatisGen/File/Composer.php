<?php

namespace BeanstalkSatisGen\File;

class Composer extends Json
{

    /**
     * Returns whether or not this composer file is a satis package based on the
     * contents.
     *
     * @return boolean
     */
    public function isComposerPackage()
    {
        return
            ! empty($this->content) &&
            $this->hasName() &&
            (
                $this->hasPackageType() ||
                $this->isSatisPackage()
            );
    }

    /**
     * Whether or not this composer file has a name
     */
    protected function hasName()
    {
        return ! empty($this->content->name);
    }

    /**
     * Whether or not this composer.json has a type that is a package
     *
     * @return bool
     */
    protected function hasPackageType()
    {
        $allowableTypes = array(
            'library',
            'wordpress-plugin',
            'wordpress-muplugin',
            'wordpress-theme',
            'symfony-bundle',
            'drupal-module'
        );

        return isset($this->content->type) && in_array($this->content->type, $allowableTypes);
    }

    /**
     * Whether or not this composer is a satis package
     *
     * @return bool
     */
    protected function isSatisPackage()
    {
        return
            isset($this->content->extra) &&
            isset($this->content->extra->{'satis-package'}) &&
            true === $this->content->extra->{'satis-package'};
    }
}
