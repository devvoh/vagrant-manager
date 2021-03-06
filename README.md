# README #

`vm` is a simple little tool that makes working with multiple vagrant boxes much easier. It can be run from any directory (once set up to be used globally).

To use it globally:

```bash
sudo ln -s /full/path/to/vagrant-manager/vm /usr/local/bin/vm
```

You'll also need to run `composer install` in the directory where vagrant-manager is placed.

You can now run it from anywhere, and pulling the latest code from git is painless.

Then you can run it like so:

```bash
vm list
```

To reload all currently running boxes:

```bash
vm reload-all
```

And for all `-all` commands, you can add a filter option:

```bash
vm reload-all --filter=part
```

#### Default values

`vm` will pick up on two env values. `VM_DEFAULT_COMMAND` and `VM_DEFAULT_BOX`. If you export those in your shell, they will be picked up and applied where necessary.

If you set these to:

```bash
VM_DEFAULT_BOX=box VM_DEFAULT_COMMAND=ssh
```

Then running `vm` without arguments will `ssh` into `box`.

If you run `vm halt`, it'll do so on `box`.

#### To-do
1. Make the default flow recognize whether it's a machine or command. This would mean that `vm box` would `ssh` into it, and `vm halt` would `halt` box.
