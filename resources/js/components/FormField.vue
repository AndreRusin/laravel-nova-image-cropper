<template>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
        <template slot="field">
            <div class="upload-image" v-show="fileUploaded">
                <cropper
                    ref="cropper"
                    class="upload-image__cropper"
                    :src="image.src"

                    :stencil-props="{
                        handlers: {},
                        movable: false,
                        resizable: false,
                        aspectRatio: 1,
                    }"
                    :stencil-size="{
                        width: 700,
                        height: 700
                    }"
                    image-restriction="stencil"
                />

                <button type="button" class="crop-button" @click="crop">
                    <span>{{ __('Crop Image') }}</span>
                </button>
            </div>

            <span
                v-if="shouldShowField"
                class="form-file mr-4 bt-5"
                :class="{ 'opacity-75': isReadonly }"
            >
                <input
                    ref="fileField"
                    :dusk="field.attribute"
                    class="form-file-input select-none"
                    type="file"
                    :id="idAttr"
                    name="name"
                    @change="loadImage($event)"
                    :disabled="isReadonly"
                    :accept="field.acceptedTypes"
                />
                <label
                    :for="labelFor"
                    class="form-file-btn btn btn-default btn-primary select-none"
                >
                    <span>{{ __('Choose Image') }}</span>
                </label>
            </span>

            <DeleteButton
                :dusk="field.attribute + '-delete-link'"
                v-if="fileUploaded"
                @click="confirmRemoval"
            >
                <span class="class ml-2 mt-1"> {{ __('Remove Image') }} </span>
            </DeleteButton>
            <portal to="modals">
                <confirm-upload-removal-modal
                    v-if="removeModalOpen"
                    @confirm="removeFile"
                    @close="closeRemoveModal"
                />
            </portal>

        </template>
  </default-field>
</template>

<script>
import {FormField, HandlesValidationErrors, Errors} from 'laravel-nova'
import DeleteButton from './DeleteButton'
import {Cropper} from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css';
import 'vue-advanced-cropper/dist/theme.compact.css';

// This function is used to detect the actual image type,
function getMimeType(file, fallback = null) {
    const byteArray = (new Uint8Array(file)).subarray(0, 4);
    let header = '';
    for (let i = 0; i < byteArray.length; i++) {
        header += byteArray[i].toString(16);
    }
    switch (header) {
        case "89504e47":
            return "image/png";
        case "47494638":
            return "image/gif";
        case "ffd8ffe0":
        case "ffd8ffe1":
        case "ffd8ffe2":
        case "ffd8ffe3":
        case "ffd8ffe8":
            return "image/jpeg";
        default:
            return fallback;
    }
}

export default {
    mixins: [FormField, HandlesValidationErrors],

    components: {
        Cropper,
        DeleteButton,
    },

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            image: {
                src: null,
                type: null
            },
            file: null,
            fileName: null,
            removeModalOpen: false,
            uploadErrors: new Errors(),
            fileInputEl: null
        };
    },

    mounted() {
        if (this.field.previewUrl) {
            const imageType = this.field.previewUrl.split(',,,')[0]
            const imageBase64 = this.field.previewUrl.split(',,,')[1]
            this.file = this.b64toBlob(imageBase64, imageType)
            this.updateImageAfterCrop()
        }
        this.field.fill = (formData) => {
            if (this.file) {
                formData.append(this.field.attribute, this.file, this.fileName)
            }
        }
    },

    computed: {
        /**
         * Determine whether the file field input should be shown.
         */
        shouldShowField() {
            return Boolean(!this.isReadonly)
        },

        /**
         * The ID attribute to use for the file field.
         */
        idAttr() {
            return this.labelFor
        },

        /**
         * The label attribute to use for the file field.
         */
        labelFor() {
            let name = this.resourceName

            if (this.relatedResourceName) {
                name += '-' + this.relatedResourceName
            }

            return `file-${name}-${this.field.attribute}`
        },

        fileUploaded() {
            return this.file !== null
        },

        /**
         * Determine if should hit the endpoint for delete
         */
        imageExistsOnServer() {
            return Boolean(this.field.previewUrl)
        }
    },

    methods: {
        crop() {
            const self = this
            const { canvas } = this.$refs.cropper.getResult();
            canvas.toBlob((blob) => {
                self.file = blob
                self.updateImageAfterCrop()
            }, this.image.type);
        },

        reset() {
            if (this.image.src) {
                URL.revokeObjectURL(this.image.src)
            }

            if (this.fileInputEl !== null) {
                this.fileInputEl.value = ''
            }

            this.image = {
                src: null,
                type: null
            }
            this.file = null
        },

        loadImage(event) {
            if (this.fileInputEl === null) {
                this.fileInputEl = event.target
            }

            const { files } = event.target;
            let path = event.target.value
            this.fileName = path.match(/[^\\/]*$/)[0]

            if (files && files[0]) {
                this.file = files[0]
                if (this.image.src) {
                    URL.revokeObjectURL(this.image.src)
                }
                const blob = URL.createObjectURL(files[0]);

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.image = {
                        src: blob,
                        type: getMimeType(e.target.result, files[0].type),
                    };
                };
                reader.readAsArrayBuffer(files[0]);
            }
        },

        updateImageAfterCrop() {
            if (this.file) {
                if (this.image.src) {
                    URL.revokeObjectURL(this.image.src)
                }
                const blob = URL.createObjectURL(this.file);

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.image = {
                        src: blob,
                        type: getMimeType(e.target.result, this.file.type),
                    };
                };
                reader.readAsArrayBuffer(this.file);
            }
        },

        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            this.value = this.field.value || ''
        },

        b64toBlob(b64Data, contentType = '', sliceSize=512) {
            const byteCharacters = atob(b64Data);
            const byteArrays = [];

            for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
                const slice = byteCharacters.slice(offset, offset + sliceSize);

                const byteNumbers = new Array(slice.length);
                for (let i = 0; i < slice.length; i++) {
                    byteNumbers[i] = slice.charCodeAt(i);
                }

                const byteArray = new Uint8Array(byteNumbers);
                byteArrays.push(byteArray);
            }

            return new Blob(byteArrays, {type: contentType});
        },

        /**
         * Confirm removal of the linked file
         */
        confirmRemoval() {
            this.removeModalOpen = true
        },

        /**
         * Close the upload removal modal
         */
        closeRemoveModal() {
            this.removeModalOpen = false
        },

        /**
         * Remove the linked file from storage
         */
        async removeFile() {
            if (!this.imageExistsOnServer) {
                this.closeRemoveModal()
                this.reset()
                return
            }

            this.uploadErrors = new Errors()

            const {
                resourceName,
                resourceId,
                relatedResourceName,
                relatedResourceId,
                viaRelationship,
            } = this
            const attribute = this.field.attribute

            const uri =
                this.viaRelationship &&
                this.relatedResourceName &&
                this.relatedResourceId
                    ? `/nova-api/${resourceName}/${resourceId}/${relatedResourceName}/${relatedResourceId}/field/${attribute}?viaRelationship=${viaRelationship}`
                    : `/nova-api/${resourceName}/${resourceId}/field/${attribute}`

            try {
                await Nova.request().delete(uri)
                this.closeRemoveModal()
                this.deleted = true
                this.reset()
                this.$emit('file-deleted')
                Nova.success(this.__('The file was deleted!'))
            } catch (error) {
                this.closeRemoveModal()

                if (error.response.status == 422) {
                    this.uploadErrors = new Errors(error.response.data.errors)
                }
            }
        },
    },

    destroyed() {
        // Revoke the object URL, to allow the garbage collector to destroy the uploaded before file
        if (this.image.src) {
            URL.revokeObjectURL(this.image.src)
        }
    }
}
</script>
<style lang="scss">
.upload-image {
    position: relative;
    -webkit-user-select: none;
    -ms-user-select: none;
    user-select: none;
    margin-top: 20px;
    margin-bottom: 20px;
    width: 100%;

    &__cropper {
        border: solid 1px #eee;
        min-height: 300px;
        max-height: 400px;
        width: 100%;
    }

    .crop-button {
        display: flex;
        justify-content: center;
        position: absolute;
        left: 50%;
        top: 1px;
        transform: translateX(-50%);
        background: rgba(126,142,161,.65);
        padding: 5px 20px;
        transition: background .5s;
        border-radius: 0 0 5px 5px;
        color: #fff;
        font-size: 16px;
        cursor: pointer;

        &:hover {
            background: rgba(126,142,161,.9);
        }

        &:focus {
            outline: none;
        }
    }
}
</style>
