# The following statements ensure that the inventory
# module is sync'd to the minion and that the
# inventory.audit scheduled job is in place.

# Sync pillar data on the minion
refresh:
  module.run:
    - name: saltutil.refresh_pillar
    - order: 1
