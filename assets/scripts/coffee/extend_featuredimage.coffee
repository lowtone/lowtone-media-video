media = @wp.media
FeaturedImage = media.controller.FeaturedImage
old_initialize = FeaturedImage.prototype.initialize

@wp.media.controller.FeaturedImage = FeaturedImage.extend
	initialize: ->
		this.set 'library', media.query({type: ['image', 'video']}) if !this.get 'library'

		old_initialize.call this