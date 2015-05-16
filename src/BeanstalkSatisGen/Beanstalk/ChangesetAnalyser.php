<?php

namespace BeanstalkSatisGen\Beanstalk;

class ChangesetAnalyser
{

    protected $beanstalkApi;

    /**
     * @param Api $beanstalkApi
     */
    public function __construct(Api $beanstalkApi)
    {
        $this->beanstalkApi = $beanstalkApi;
    }

    /**
     * Analyses the changeset stream of beanstalk until a given git hash
     *
     * @param string $toGitHash The git hash to analyse towards
     * @param ChangesetSearch $changesetSearch Search to look for in the changeset
     * @return array An array of changeset that fit the changeset search
     */
    public function analyse($toGitHash, ChangesetSearch $changesetSearch)
    {
        $allChangesets = $this->getAllChangesetsUntil($toGitHash);

        $allChangesets = array_filter($allChangesets, function($changeset) use ($changesetSearch) {
            $validates = false;

            $changes = $changeset->changed_files;
            foreach ($changes as $change) {
                if ($changesetSearch->validates($change[0], $change[1])) {
                    $validates = true;
                    break;
                }
            }

            return $validates;
        });

        return $allChangesets;
    }

    /**
     * Returns all changeset until a certain changeset, limits to 990
     *
     * @param string $gitHash The git hash to search to
     * @return array An array of changesets until the given changeset
     */
    public function getAllChangesetsUntil($gitHash)
    {
        $allChangesets = array();
        $page = 1;
        $found = false;
        do {
            $newChangesets = $this->beanstalkApi->loadJson('changesets', array('per_page' => 30, 'page' => $page));

            $newChangesets = array_map(function($changeset) {
                return $changeset->revision_cache;
            }, $newChangesets);

            $hashes = array_map(function($changeset) {
                return $changeset->hash_id;
            }, $newChangesets);

            if (in_array($gitHash, $hashes)) {
                $found = true;

                do {
                    $removedHash = array_pop($hashes);
                    array_pop($newChangesets);
                } while ($removedHash !== $gitHash && count($hashes) > 0);
            }

            $allChangesets = array_merge($newChangesets, $allChangesets);

            $page++;
        } while (!$found && count($allChangesets) <= 990);

        return $allChangesets;
    }
}
