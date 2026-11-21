import GenerationController from './GenerationController'
import Settings from './Settings'

const Controllers = {
    GenerationController: Object.assign(GenerationController, GenerationController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers