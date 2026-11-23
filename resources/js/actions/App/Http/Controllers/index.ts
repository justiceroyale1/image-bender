import NewImageGenerationPageController from './NewImageGenerationPageController'
import ImageGenerationController from './ImageGenerationController'
import Settings from './Settings'

const Controllers = {
    NewImageGenerationPageController: Object.assign(NewImageGenerationPageController, NewImageGenerationPageController),
    ImageGenerationController: Object.assign(ImageGenerationController, ImageGenerationController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers