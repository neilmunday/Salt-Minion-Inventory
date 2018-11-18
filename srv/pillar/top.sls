# Include inventory.sls your pillar to schedule
# regular audit checks.

base:
  '*':
    - inventory
