<template>
    <img
        v-if="imageUrl"
        :src="imageUrl"
        style="object-fit: cover"
        class="align-bottom w-8 h-8"
        :class="{ 'rounded-full': field.rounded, rounded: !field.rounded }"
    />
    <span v-else>&mdash;</span>
</template>

<script>
export default {
    props: ['resourceName', 'field'],
    computed: {
        imageUrl() {
            return this.field.thumbnailUrl
        },
    },
}
</script>
