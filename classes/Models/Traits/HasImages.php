<?php namespace Bkwld\Decoy\Models\Traits;

// Dependencies
use Bkwld\Decoy\Models\Image;
use Illuminate\Database\Eloquent\Builder;
use Event;

/**
 * All models that should support images should inherit this trait.  This gets
 * used by the Base Model.  This logic lives as a trait mostly to keep the
 * base model cleaner.
 */
trait HasImages {
	use SerializeWithImages;

	/**
	 * Boot events
	 *
	 * @return void
	 */
	public static function bootHasImages() {

		// Automatically add images relationship to the cleoneable relations
		Event::listen('cloner::cloning: '.get_called_class(), function($clone, $src) {
			$src->addCloneableRelation('images');
		});

		// Delete all Images if the parent is deleted.  Need to use "each" to get
		// the Image deleted events to fire.
		static::deleted(function($model) {
			$model->images->each(function($image) {
				$image->delete();
			});
		});

		// Automatically eager load the images relationship
		static::addGlobalScope('images', function(Builder $builder) {
			$builder->with('images');
		});
	}

	/**
	 * Polymorphic relationship
	 */
	public function images() {
		return $this->morphMany(Image::class, 'imageable');
	}

	/**
	 * Get a specific Image by searching the eager loaded Images collection for
	 * one matching the name.  If $name can't be found, return an empty Image.
	 *
	 * @param  string $name The "name" field from the db
	 * @return Image
	 */
	public function img($name = null) {
		return $this->images->first(function($key, Image $image) use ($name) {
			return $image->getAttribute('name') == $name;

		// When the $name isn't found, return an empty Image object so all the
		// accessors can be invoked and will return an empty string.
		}) ?: new Image;
	}

	/**
	 * Add an Image to the `imgs` attribute of the model for the purpose of
	 * exposing it when serialized.
	 *
	 * @param  Image  $image
	 * @param  string $property
	 * @return $this
	 */
	public function appendToImgs(Image $image, $property) {

		// Create or fetch the container for all images on the model. The
		// container could not be "images" because that is used by the
		// relationship function and leads to trouble.
		if (!$this->getAttribute('imgs')) {
			$imgs = [];
			$this->addVisible('imgs');
		} else {
			$imgs = $this->getAttribute('imgs');
		}

		// Add the image to the container and set it.  Then return the model. It
		// must be explicitly converted to an array because Laravel won't
		// automatically do it during collection serialization. Another, more
		// complicated approach could have been to use the Decoy Base model to add
		// a cast type of "model" and then call toArray() on it when casting the
		// attribute.
		$imgs[$property] = $image->toArray();
		$this->setAttribute('imgs', $imgs);
		return $this;
	}

	/**
	 * Generate the configuration used by roumen/sitemap for generating sitemap
	 * xml files
	 *
	 * @return array
	 */
	public function getSitemapImagesAttribute() {
		return $this->images->map(function($image) {
			return [
				'url' => $image->url,
				'title' => $image->title,
			];
		})->all();
	}

}
