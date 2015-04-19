# beanstalk-satis-gen
Beanstalk Satis Generator

A simple tool that allows you to add repositories to a Satis JSON file by reading repositories from your Beanstalk account and adding all repositories that are Composer packages.

You can use the tool by calling the included PHP classes directly, but the main use case is calling the included shell script `bin/update`.

## Usage example

```
bin/update config.json satis.json
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
This will scan all Git repositories that the specified user can access under the specified account. If the main branch of the repository has a composer.json file in the root, and that composer.json file contains a "name" key, then a repository will be added to the satis.json:

```
{"type": "vcs", "url": "the-repository-url"}
```

Only repositories that weren't already present are added, so it's safe to run the script several times. Exisiting repository definitions will be left alone: the script does not delete or update them.

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
