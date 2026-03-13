<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Budget Management</h1>
        <p class="text-sm text-gray-500">Categories and items — total from items, progress from COVERED status</p>
      </div>
      <div v-if="authStore.can('budget.add')" class="flex gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-medium text-rose-600 shadow-soft transition hover:bg-rose-50"
          @click="openItemModal()"
        >
          + Add item
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
          @click="openCategoryModal()"
        >
          + Add category
        </button>
      </div>
    </div>

    <!-- Progress cards (first 4 categories: total from items, progress from COVERED) -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <ProgressCard
        v-for="cat in topCategories"
        :key="cat.id"
        :label="cat.name"
        :value="categoryProgress(cat)"
      />
      <div
        v-if="categoryStore.categories.length === 0"
        class="rounded-2xl border border-dashed border-rose-200 bg-rose-50/20 p-6 text-center text-sm text-gray-500"
      >
        Add categories and items to see progress
      </div>
    </div>

    <p v-if="categoryStore.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">
      {{ categoryStore.errorMessage }}
    </p>
    <p v-if="itemStore.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">
      {{ itemStore.errorMessage }}
    </p>

    <!-- Categories table: Total = sum of items, Progress = % COVERED -->
    <TableComponent
      title="Budget categories"
      :columns="categoryColumns"
      :data="categoriesWithTotals"
      row-key="id"
      :pagination="true"
      :page-size="5"
      v-model:current-page="categoryPage"
      empty="No categories yet"
    >
      <template #cell-total="{ row }">{{ formatUgx(categoryTotal(row)) }}</template>
      <template #cell-progress="{ row }">{{ categoryProgress(row) }}%</template>
      <template #cell-description="{ value }">
        <span class="text-gray-500">{{ value || '—' }}</span>
      </template>
      <template #actions="{ row }">
        <div v-if="authStore.can('budget.edit') || authStore.can('budget.delete')" class="flex items-center justify-end gap-2">
          <button v-if="authStore.can('budget.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openCategoryModal(row)">
            Edit
          </button>
          <button v-if="authStore.can('budget.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium" @click="confirmDeleteCategory(row)">
            Delete
          </button>
        </div>
      </template>
    </TableComponent>

    <!-- Budget items table -->
    <TableComponent
      title="Budget items"
      :columns="itemColumns"
      :data="filteredItems"
      row-key="id"
      :pagination="true"
      :page-size="8"
      v-model:current-page="itemPage"
      empty="No items yet"
    >
      <template #toolbar>
        <TableToolbar
          v-model:search-query="itemSearchQuery"
          :export-disabled="!filteredItems.length"
          @export-excel="exportItemsExcel"
          @export-pdf="exportItemsPdf"
        >
          <template #filters>
            <select v-model="itemFilterCategory" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
              <option value="">All categories</option>
              <option v-for="c in categoryStore.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="itemFilterStatus" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
              <option value="">All statuses</option>
              <option value="COVERED">Covered</option>
              <option value="NOT_COVERED">Not covered</option>
            </select>
          </template>
        </TableToolbar>
      </template>
      <template #cell-budget_category_id="{ value }">{{ categoryName(value) }}</template>
      <template #cell-quantity="{ value }">{{ Number(value) }}</template>
      <template #cell-unit_amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-cost="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-status="{ value }">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
          :class="value === 'COVERED' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
        >
          {{ value === 'COVERED' ? 'Covered' : 'Not covered' }}
        </span>
      </template>
      <template #cell-covered_type="{ row }">
        <span v-if="row.status === 'COVERED'" class="text-gray-600 text-sm">
          {{ row.covered_type === 'FROM_CONTRIBUTIONS' ? 'From contributions' : 'By default (not from contributions)' }}
        </span>
        <span v-else class="text-gray-400">—</span>
      </template>
      <template #actions="{ row }">
        <div v-if="authStore.can('budget.edit') || authStore.can('budget.delete')" class="flex items-center justify-end gap-2">
          <button v-if="authStore.can('budget.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openItemModal(row)">
            Edit
          </button>
          <button v-if="authStore.can('budget.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium" @click="confirmDeleteItem(row)">
            Delete
          </button>
        </div>
      </template>
    </TableComponent>

    <!-- Category Add/Edit modal -->
    <ModalComponent v-model="showCategoryModal" :title="editingCategoryId ? 'Edit category' : 'Add category'" @update:model-value="editingCategoryId = null">
      <form class="space-y-4" @submit.prevent="saveCategory">
        <FormInput
          v-model="categoryForm.name"
          label="Category name"
          placeholder="e.g. Bride, Venue, Catering"
          required
          :error="categoryErrors.name"
        />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
          <textarea
            v-model="categoryForm.description"
            placeholder="e.g. Bridal attire and accessories"
            rows="2"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm placeholder-gray-500 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          />
        </div>
        <p v-if="categoryStore.errorMessage" class="text-sm text-rose-600">{{ categoryStore.errorMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showCategoryModal = false">
            Cancel
          </button>
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="savingCategory">
            {{ savingCategory ? 'Saving…' : (editingCategoryId ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </ModalComponent>

    <!-- Item Add/Edit modal -->
    <ModalComponent v-model="showItemModal" :title="editingItemId ? 'Edit item' : 'Add item'" @update:model-value="editingItemId = null">
      <form class="space-y-4" @submit.prevent="saveItem">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Category <span class="text-rose-500">*</span></label>
          <select
            v-model.number="itemForm.budget_category_id"
            required
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          >
            <option value="">Select category</option>
            <option v-for="c in categoryStore.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="itemErrors.budget_category_id" class="text-xs text-rose-500">{{ itemErrors.budget_category_id }}</p>
        </div>
        <FormInput
          v-model="itemForm.item_name"
          label="Item name"
          placeholder="e.g. Bridal Gown, Shoes"
          required
          :error="itemErrors.item_name"
        />
        <FormInput
          v-model.number="itemForm.quantity"
          label="Quantity"
          type="number"
          min="0"
          step="1"
          placeholder="1"
          :error="itemErrors.quantity"
        />
        <FormInput
          v-model.number="itemForm.unit_amount"
          label="Unit amount (UGX)"
          type="number"
          min="0"
          step="1000"
          placeholder="0"
          :error="itemErrors.unit_amount"
        />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Cost (UGX)</label>
          <p class="rounded-xl border border-rose-100 bg-rose-50/30 px-4 py-2.5 text-sm font-medium text-wmis-text">
            {{ formatUgx(itemCost) }}
          </p>
          <p class="text-xs text-gray-500">Quantity × Unit amount</p>
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="itemForm.status"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          >
            <option value="NOT_COVERED">Not covered</option>
            <option value="COVERED">Covered</option>
          </select>
        </div>
        <div v-if="itemForm.status === 'COVERED'" class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Covered type</label>
          <select
            v-model="itemForm.covered_type"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          >
            <option value="DEFAULT">Covered by default (not from contributions)</option>
            <option value="FROM_CONTRIBUTIONS">Covered from contributions</option>
          </select>
        </div>
        <p v-if="itemStore.errorMessage" class="text-sm text-rose-600">{{ itemStore.errorMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showItemModal = false">
            Cancel
          </button>
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="savingItem">
            {{ savingItem ? 'Saving…' : (editingItemId ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </ModalComponent>

    <!-- Delete category confirm -->
    <ModalComponent v-model="showDeleteCategoryModal" title="Delete category" @update:model-value="deleteCategoryTarget = null">
      <p class="text-sm text-gray-600">
        Delete <strong>{{ deleteCategoryTarget?.name }}</strong>? All items in this category will be deleted. This cannot be undone.
      </p>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showDeleteCategoryModal = false">
          Cancel
        </button>
        <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="deletingCategory" @click="doDeleteCategory">
          {{ deletingCategory ? 'Deleting…' : 'Delete' }}
        </button>
      </div>
    </ModalComponent>

    <!-- Delete item confirm -->
    <ModalComponent v-model="showDeleteItemModal" title="Delete item" @update:model-value="deleteItemTarget = null">
      <p class="text-sm text-gray-600">
        Delete <strong>{{ deleteItemTarget?.item_name }}</strong>? This cannot be undone.
      </p>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showDeleteItemModal = false">
          Cancel
        </button>
        <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="deletingItem" @click="doDeleteItem">
          {{ deletingItem ? 'Deleting…' : 'Delete' }}
        </button>
      </div>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import ProgressCard from '@/components/ProgressCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import TableToolbar from '@/components/TableToolbar.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useBudgetCategoriesStore } from '@/stores/budgetCategories'
import { useBudgetItemsStore } from '@/stores/budgetItems'
import { useAuthStore } from '@/stores/auth'
import { useTableExport } from '@/composables/useTableExport'

const categoryStore = useBudgetCategoriesStore()
const itemStore = useBudgetItemsStore()
const authStore = useAuthStore()

const categoryPage = ref(1)
const itemPage = ref(1)
const itemSearchQuery = ref('')
const itemFilterCategory = ref('')
const itemFilterStatus = ref('')
const showCategoryModal = ref(false)
const showItemModal = ref(false)
const showDeleteCategoryModal = ref(false)
const showDeleteItemModal = ref(false)
const editingCategoryId = ref(null)
const editingItemId = ref(null)
const deleteCategoryTarget = ref(null)
const deleteItemTarget = ref(null)
const savingCategory = ref(false)
const savingItem = ref(false)
const deletingCategory = ref(false)
const deletingItem = ref(false)

const categoryColumns = [
  { key: 'name', label: 'Category' },
  { key: 'total', label: 'Total (UGX)' },
  { key: 'progress', label: 'Progress' },
  { key: 'description', label: 'Description' }
]

const itemColumns = [
  { key: 'budget_category_id', label: 'Category' },
  { key: 'item_name', label: 'Item' },
  { key: 'quantity', label: 'Qty' },
  { key: 'unit_amount', label: 'Unit amount' },
  { key: 'cost', label: 'Cost' },
  { key: 'status', label: 'Status' },
  { key: 'covered_type', label: 'Covered type' }
]

const categoryForm = reactive({ name: '', description: '' })
const categoryErrors = reactive({ name: '' })

const itemForm = reactive({
  budget_category_id: '',
  item_name: '',
  quantity: 1,
  unit_amount: 0,
  status: 'NOT_COVERED',
  covered_type: 'DEFAULT'
})
const itemErrors = reactive({ budget_category_id: '', item_name: '', quantity: '', unit_amount: '' })

const topCategories = computed(() => categoryStore.categories.slice(0, 4))

const categoriesWithTotals = computed(() => categoryStore.categories)

function categoryTotal(cat) {
  return itemStore.items
    .filter((i) => Number(i.budget_category_id) === Number(cat.id))
    .reduce((sum, i) => sum + (Number(i.cost) || 0), 0)
}

function categoryProgress(cat) {
  const total = categoryTotal(cat)
  if (total <= 0) return 0
  const covered = itemStore.items
    .filter((i) => Number(i.budget_category_id) === Number(cat.id) && i.status === 'COVERED')
    .reduce((sum, i) => sum + (Number(i.cost) || 0), 0)
  return Math.min(100, Math.round((covered / total) * 100))
}

function categoryName(categoryId) {
  const c = categoryStore.categories.find((x) => Number(x.id) === Number(categoryId))
  return c ? c.name : '—'
}

const itemExportColumns = [
  { key: 'category_name', label: 'Category' },
  { key: 'item_name', label: 'Item' },
  { key: 'quantity', label: 'Qty' },
  { key: 'unit_amount', label: 'Unit amount' },
  { key: 'cost', label: 'Cost' },
  { key: 'status_label', label: 'Status' },
  { key: 'covered_type_label', label: 'Covered type' }
]
const { exportToExcel, exportToPdf } = useTableExport(itemExportColumns, 'Budget items')

const filteredItems = computed(() => {
  let list = itemStore.items || []
  const q = (itemSearchQuery.value || '').trim().toLowerCase()
  if (q) {
    list = list.filter((i) => {
      const name = (i.item_name || '').toLowerCase()
      const catName = (categoryName(i.budget_category_id) || '').toLowerCase()
      return name.includes(q) || catName.includes(q)
    })
  }
  if (itemFilterCategory.value) list = list.filter((i) => Number(i.budget_category_id) === Number(itemFilterCategory.value))
  if (itemFilterStatus.value) list = list.filter((i) => (i.status || '') === itemFilterStatus.value)
  return list
})

function itemsForExport() {
  return filteredItems.value.map((i) => ({
    ...i,
    category_name: categoryName(i.budget_category_id),
    status_label: i.status === 'COVERED' ? 'Covered' : 'Not covered',
    covered_type_label: i.status === 'COVERED' ? (i.covered_type === 'FROM_CONTRIBUTIONS' ? 'From contributions' : 'By default') : '—'
  }))
}
function budgetPdfStatistics() {
  const items = filteredItems.value
  const totalAmount = items.reduce((sum, i) => sum + (Number(i.cost) || 0), 0)
  return [
    { label: 'Total amount', value: formatUgx(totalAmount) },
    { label: 'Number of items', value: String(items.length) }
  ]
}
function exportItemsExcel() {
  exportToExcel(itemsForExport(), 'budget-items')
}
function exportItemsPdf() {
  exportToPdf(itemsForExport(), 'budget-items', budgetPdfStatistics())
}

const itemCost = computed(() => {
  const q = Number(itemForm.quantity) || 0
  const u = Number(itemForm.unit_amount) || 0
  return q * u
})

function openCategoryModal(row = null) {
  categoryStore.clearError()
  categoryErrors.name = ''
  if (row) {
    editingCategoryId.value = row.id
    categoryForm.name = row.name || ''
    categoryForm.description = row.description || ''
  } else {
    editingCategoryId.value = null
    categoryForm.name = ''
    categoryForm.description = ''
  }
  showCategoryModal.value = true
}

async function saveCategory() {
  categoryErrors.name = ''
  if (!categoryForm.name.trim()) {
    categoryErrors.name = 'Name is required'
    return
  }
  savingCategory.value = true
  categoryStore.clearError()
  try {
    if (editingCategoryId.value) {
      await categoryStore.updateCategory(editingCategoryId.value, {
        name: categoryForm.name.trim(),
        description: categoryForm.description.trim() || null
      })
    } else {
      await categoryStore.createCategory({
        name: categoryForm.name.trim(),
        planned_amount: 0,
        description: categoryForm.description.trim() || null
      })
    }
    showCategoryModal.value = false
  } finally {
    savingCategory.value = false
  }
}

function confirmDeleteCategory(row) {
  deleteCategoryTarget.value = row
  showDeleteCategoryModal.value = true
}

async function doDeleteCategory() {
  if (!deleteCategoryTarget.value) return
  deletingCategory.value = true
  categoryStore.clearError()
  try {
    await categoryStore.deleteCategory(deleteCategoryTarget.value.id)
    await itemStore.fetchItems()
    showDeleteCategoryModal.value = false
    deleteCategoryTarget.value = null
  } finally {
    deletingCategory.value = false
  }
}

function openItemModal(row = null) {
  itemStore.clearError()
  itemErrors.budget_category_id = ''
  itemErrors.item_name = ''
  itemErrors.quantity = ''
  itemErrors.unit_amount = ''
  if (row) {
    editingItemId.value = row.id
    itemForm.budget_category_id = row.budget_category_id
    itemForm.item_name = row.item_name || ''
    itemForm.quantity = Number(row.quantity) || 1
    itemForm.unit_amount = Number(row.unit_amount) || 0
    itemForm.status = row.status === 'COVERED' ? 'COVERED' : 'NOT_COVERED'
    itemForm.covered_type = (row.status === 'COVERED' && (row.covered_type === 'FROM_CONTRIBUTIONS' || row.covered_type === 'DEFAULT'))
      ? row.covered_type
      : 'DEFAULT'
  } else {
    editingItemId.value = null
    itemForm.budget_category_id = categoryStore.categories.length ? categoryStore.categories[0].id : ''
    itemForm.item_name = ''
    itemForm.quantity = 1
    itemForm.unit_amount = 0
    itemForm.status = 'NOT_COVERED'
    itemForm.covered_type = 'DEFAULT'
  }
  showItemModal.value = true
}

async function saveItem() {
  itemErrors.budget_category_id = ''
  itemErrors.item_name = ''
  itemErrors.quantity = ''
  itemErrors.unit_amount = ''
  let ok = true
  if (!itemForm.budget_category_id) {
    itemErrors.budget_category_id = 'Select a category'
    ok = false
  }
  if (!itemForm.item_name.trim()) {
    itemErrors.item_name = 'Item name is required'
    ok = false
  }
  const qty = Number(itemForm.quantity)
  if (isNaN(qty) || qty < 0) {
    itemErrors.quantity = 'Enter a valid quantity'
    ok = false
  }
  const ua = Number(itemForm.unit_amount)
  if (isNaN(ua) || ua < 0) {
    itemErrors.unit_amount = 'Enter a valid amount'
    ok = false
  }
  if (!ok) return

  savingItem.value = true
  itemStore.clearError()
  try {
    if (editingItemId.value) {
      await itemStore.updateItem(editingItemId.value, {
        budget_category_id: itemForm.budget_category_id,
        item_name: itemForm.item_name.trim(),
        quantity: qty,
        unit_amount: ua,
        status: itemForm.status,
        covered_type: itemForm.status === 'COVERED' ? itemForm.covered_type : null
      })
    } else {
      await itemStore.createItem({
        budget_category_id: itemForm.budget_category_id,
        item_name: itemForm.item_name.trim(),
        quantity: qty,
        unit_amount: ua,
        status: itemForm.status,
        covered_type: itemForm.status === 'COVERED' ? itemForm.covered_type : null
      })
    }
    showItemModal.value = false
  } finally {
    savingItem.value = false
  }
}

function confirmDeleteItem(row) {
  deleteItemTarget.value = row
  showDeleteItemModal.value = true
}

async function doDeleteItem() {
  if (!deleteItemTarget.value) return
  deletingItem.value = true
  itemStore.clearError()
  try {
    await itemStore.deleteItem(deleteItemTarget.value.id)
    showDeleteItemModal.value = false
    deleteItemTarget.value = null
  } finally {
    deletingItem.value = false
  }
}

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}

onMounted(async () => {
  await categoryStore.fetchCategories().catch(() => {})
  await itemStore.fetchItems().catch(() => {})
})
</script>
