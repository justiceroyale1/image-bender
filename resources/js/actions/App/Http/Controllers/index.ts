import NewImageGenerationPageController from './NewImageGenerationPageController'
import ImageGenerationController from './ImageGenerationController'
import PixelforgeWebhookController from './PixelforgeWebhookController'
import Settings from './Settings'

const Controllers = {
    NewImageGenerationPageController: Object.assign(NewImageGenerationPageController, NewImageGenerationPageController),
    ImageGenerationController: Object.assign(ImageGenerationController, ImageGenerationController),
    PixelforgeWebhookController: Object.assign(PixelforgeWebhookController, PixelforgeWebhookController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers