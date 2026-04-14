<template>
  <section id="pricing" class="bg-brand-50/50 py-16">
    <div class="mx-auto w-full max-w-7xl px-6">
      <div class="mx-auto max-w-3xl text-center">
        <p class="ui-label text-brand-600">Pricing</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-900">Simple plans for every school size</h2>
        <p class="mt-3 text-slate-600">
          Start small, scale quickly, and choose the plan that matches your institution's needs.
        </p>
      </div>

      <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <article
          v-for="plan in plans"
          :key="plan.name"
          class="rounded-2xl border bg-white p-7 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-card-hover"
          :class="plan.popular ? 'border-brand-300 ring-2 ring-brand-100' : 'border-sc-border'"
        >
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">{{ plan.name }}</h3>
            <span v-if="plan.popular" class="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700">
              Most Popular
            </span>
          </div>

          <p class="text-sm text-slate-600">{{ plan.description }}</p>

          <div class="mt-5 flex items-end gap-1">
            <p class="text-3xl font-bold text-slate-900">{{ plan.price }}</p>
            <p class="pb-1 text-sm text-slate-500">/month</p>
          </div>

          <ul class="mt-6 space-y-2 text-sm text-slate-600">
            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
              <span class="mt-1 h-2 w-2 rounded-full bg-brand-500" />
              <span>{{ feature }}</span>
            </li>
          </ul>

          <RouterLink
            to="/request-demo"
            class="mt-7 inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold transition"
            :class="
              plan.popular
                ? 'bg-brand-600 text-white hover:bg-brand-700'
                : 'border border-sc-line bg-white text-slate-700 hover:bg-slate-50'
            "
          >
            Request Demo
          </RouterLink>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
const plans = [
  {
    name: 'Starter',
    price: '$49',
    description: 'For small schools getting started with digital operations.',
    popular: false,
    features: ['Up to 500 students', 'Core academics module', 'Basic fee tracking', 'Email support']
  },
  {
    name: 'Professional',
    price: '$129',
    description: 'Ideal for growing schools that need full operational coverage.',
    popular: true,
    features: ['Up to 2,000 students', 'Full academics + finance', 'Parent & student portals', 'Priority support']
  },
  {
    name: 'Enterprise',
    price: '$299',
    description: 'For large institutions requiring performance and custom workflows.',
    popular: false,
    features: ['Unlimited students', 'Advanced analytics', 'Custom integrations', 'Dedicated success manager']
  }
]
</script>

