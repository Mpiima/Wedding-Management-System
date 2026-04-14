<template>
  <div class="page-shell">
    <PageHeader title="Canteen Items" description="Item catalog, pricing, and stock levels (mock).">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
        <Button @click="openAdd">Add Item</Button>
      </template>
    </PageHeader>

    <DataTable
      title="Items"
      :columns="cols"
      :data="itemsRows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="8"
      empty-text="No canteen items yet"
    >
      <template #cell-price="{ value }">
        {{ formatCurrency(value) }}
      </template>

      <template #cell-stock="{ value }">
        <span class="font-medium">{{ value }}</span>
      </template>

      <template #actions="{ row }">
        <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="openEdit(row)">Edit</Button>
      </template>
    </DataTable>

    <Modal v-model="showModal" :title="modalTitle">
      <div class="space-y-4">
        <FormInput v-model="form.name" label="Item name" placeholder="e.g., Milk" required />
        <FormInput v-model="form.price" label="Price (UGX)" placeholder="e.g., 1500" required />
        <FormInput v-model="form.stock" label="Stock" placeholder="e.g., 25" required />
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="showModal = false">Cancel</Button>
          <Button @click="save">Save</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import { formatCurrency } from '@/utils/formatters'
import { useCanteenStore } from '@/stores/canteen'
import useNotificationStore from '@/stores/notificationStore'

const canteen = useCanteenStore()
const { errorToast, successToast } = useNotificationStore()

const showModal = ref(false)
const editingId = ref(null)
const form = reactive({
  name: '',
  price: '',
  stock: ''
})

const cols = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'price', label: 'Price', sortable: true },
  { key: 'stock', label: 'Stock', sortable: true }
]

const itemsRows = computed(() => canteen.items.value)

const modalTitle = computed(() => (editingId.value ? 'Edit Item' : 'Add Item'))

function refresh() {
  return canteen.fetchAll()
}

function openAdd() {
  editingId.value = null
  form.name = ''
  form.price = ''
  form.stock = ''
  showModal.value = true
}

function openEdit(row) {
  editingId.value = row.id
  form.name = row.name ?? ''
  form.price = String(row.price ?? '')
  form.stock = String(row.stock ?? '')
  showModal.value = true
}

function save() {
  const payload = {
    name: String(form.name || '').trim(),
    price: Number(form.price),
    stock: Number(form.stock)
  }

  if (!payload.name || Number.isNaN(payload.price) || Number.isNaN(payload.stock)) {
    errorToast('Validation Error', 'Please provide a valid name, price, and stock.')
    return
  }

  if (editingId.value) {
    canteen.updateItem(editingId.value, payload)
  } else {
    canteen.addItem(payload)
  }

  showModal.value = false
  successToast('Item Saved', editingId.value ? 'Canteen item updated successfully.' : 'Canteen item added successfully.')
}

onMounted(refresh)
</script>

