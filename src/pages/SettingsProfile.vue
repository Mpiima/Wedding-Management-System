<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Wedding Profile</h1>
      <p class="text-sm text-gray-500">Couple details, wedding date, and venue information</p>
    </div>
    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-2xl">
      <form class="space-y-4">
        <FormInput label="Bride name" placeholder="Full name" />
        <FormInput label="Groom name" placeholder="Full name" />
        <FormInput label="Wedding date" type="date" />
        <FormInput label="Venue name" placeholder="Venue" />
        <FormInput label="Venue address" placeholder="Address" />
        <div class="flex justify-end gap-2 pt-4">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600">Save</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import FormInput from '@/components/FormInput.vue'
</script>
