# Beanstalk Satis Generator

A simple tool that allows you to add repositories to a Satis JSON file by reading repositories from your Beanstalk account and adding all repositories that are Composer packages.

You can use the tool by calling the included PHP classes directly, but the main use case is by building a phar and calling that.

## Installation

To install you can clone or download the repository and build a .phar using [box](http://box-project.org). A box config is present so using `box build` in the root of this project is enough to build the .phar. The .phar will be created in `bin/satis-update.phar`

## Usage example

```
bin/satis-update.phar generate config.json satis.json
```

**config.json:**
```
{
  "subdomain": "demo",     # list repositories on the https://demo.beanstalkapp.com/ account
  "username":  "demouser", # log in with the demouser account
  "token":     "..."       # use this access token
}
```

**satis.json:**
```
{
  "name": "My Package repository",
  "repositories": []
}
```

### Result
This will scan all Git repositories that the specified user can access under the specified account. If the main branch of the repository has a composer.json file in the root, and that composer.json file signals that it should be in satis, then the repository will be added to the satis.json:

```
{"type": "vcs", "url": "the-repository-url"}
```

Only repositories that weren't already present are added, so it's safe to run the script several times. Existing repository definitions will be left alone: the script does not delete or update them.

After the generate command is done the hash of the last changeset will be safed so future parsings won't take as long. When updating an already complete satis.json the update command should be used:
```
bin/satis-update.phar update config.json satis.json
```

This command will read all changesets since the saved hash (parsed_to in the config.json) and parse these changes to see if a new composer.json has been added or one has been edited to include the required signals.

## Package signals

For a repository to end up in the satis.json the following requirements have to be met:

- A `composer.json` must be present.
- The `name` field in the composer.json must not be empty
- The `type` field is one that absolutely implies a package or a `satis-package` key is present and true in the `extra` field, like this:

    ```
    {
        "extra": {
            "satis-package": true
        }
    }
    ```

## Advanced usage

You can add a filter to your `config.json` file:

```
{
  "subdomain": "demo", 
  "username":  "demouser",
  "token":     "...",
  "repository_filters": {
    "last_commit_within": "1 week"
  }
}
```

This will only scan repositories that have been committed to within the last week. This is useful for running the script as a cron job, since scanning many repositories for the composer.json file can get a little slow.

### Filters
The `last_commit_within` is currently the only filter. Its value is evaluated as a strtotime offset from the current time, so all strtotime offsets are valid:
- `"1 month"`
- `"2 hours"`
- etc.
