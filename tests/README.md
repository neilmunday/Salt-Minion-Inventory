# Tests

## Introduction

This directory contains the necessary files to launch a SaltStack master with the Salt Minion Inventory installed and a minion for testing/demo purposes using a Rocky 8 base image.

## Running

```bash
tests/run.sh -s SALT_VERSION
```

where `SALT_VERSION` is the version of SaltStack to use, e.g.

```bash
tests/run.sh -s 3005
```

This will build the Salt master and minion images and then use `docker compose` to launch the containers.

Once launched, you can access the Salt Minion Inventory GUI at: `http://localhost:8080`

Note: please allow ~30s for the minion(s) to appear in the GUI.

If you would like to launch more than one minion, then use the `-n` option of `run.sh` to specify the desired number of minions, e.g.

```bash
tests/run.sh -s 3004 -n 2
```
