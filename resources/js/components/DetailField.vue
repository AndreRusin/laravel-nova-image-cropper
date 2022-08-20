<template>
    <panel-item :field="field">
        <div slot="value">
            <template v-if="shouldShowLoader">
                <ImageLoader
                    :src="imageUrl"
                    :maxWidth="maxWidth"
                    :rounded="rounded"
                    @missing="value => (missing = value)"
                />
            </template>
        </div>
    </panel-item>
</template>

<script>
import ImageLoader from './ImageLoader'

export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    components: { ImageLoader },

    data: () => ({ missing: false }),

    computed: {
        imageUrl() {
            return this.field.thumbnailUrl
        },

        shouldShowLoader() {
            return Boolean(this.imageUrl)
        },

        maxWidth() {
            return this.field.maxWidth || 320
        },

        rounded() {
            return this.field.rounded
        },
    }
}
</script>
