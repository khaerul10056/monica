<?php

namespace App;

use Auth;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $dates = [
        'birthdate',
    ];

    /**
     * Eager load account with every contact.
     */
    protected $with = [
        'account',
    ];

    /**
     * Get the user associated with the contact.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the activity records associated with the contact.
     */
    public function activities()
    {
        return $this->hasMany('App\Activity')->orderBy('date_it_happened', 'desc');
    }

    /**
     * Get the activity records associated with the contact.
     */
    public function activityStatistics()
    {
        return $this->hasMany('App\ActivityStatistic');
    }

    /**
     * Get the contact records associated with the contact.
     */
    public function country()
    {
        return $this->belongsTo('App\Country');
    }

    /**
     * Get the debt records associated with the contact.
     */
    public function debts()
    {
        return $this->hasMany('App\Debt');
    }

    /**
     * Get the gift records associated with the contact.
     */
    public function gifts()
    {
        return $this->hasMany('App\Gift');
    }

    /**
     * Get the event records associated with the contact.
     */
    public function events()
    {
        return $this->hasMany('App\Event')->orderBy('created_at', 'desc');
    }

    /**
     * Get the kid records associated with the contact.
     */
    public function kids()
    {
        return $this->hasMany('App\Kid', 'child_of_contact_id');
    }

    /**
     * Get the note records associated with the contact.
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Get the reminder records associated with the contact.
     */
    public function reminders()
    {
        return $this->hasMany('App\Reminder')->orderBy('next_expected_date', 'asc');
    }

    /**
     * Get the current significant other associated with the contact.
     *
     * @return SignificantOther
     */
    public function significantOther()
    {
        return $this->hasOne('App\SignificantOther')->active();
    }

    /**
     * Get the significant others associated with the contact.
     */
    public function significantOthers()
    {
        return $this->hasMany('App\SignificantOther');
    }

    /**
     * Get the task records associated with the contact.
     */
    public function tasks()
    {
        return $this->hasMany('App\Task');
    }

    /**
     * Get the complete name of the contact.
     *
     * @return string
     */
    public function getCompleteName()
    {
        $completeName = $this->first_name;

        if (!is_null($this->middle_name)) {
            $completeName = $completeName . ' ' . $this->middle_name;
        }

        if (!is_null($this->last_name)) {
            $completeName = $completeName . ' ' . $this->last_name;
        }

        return $completeName;
    }

    /**
     * Get the first name of the contact.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Get the middle name of the contact.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middle_name;
    }

    /**
     * Get the last name of the contact.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Get the initials of the contact, used for avatars.
     *
     * @return string
     */
    public function getInitials()
    {
        preg_match_all('/(?<=\s|^)[a-zA-Z0-9]/i', $this->getCompleteName(), $initials);

        return implode('', $initials[0]);
    }

    /**
     * Get the date of the last activity done by this contact.
     *
     * @return string 'Oct 29, 1981'
     */
    public function getLastActivityDate($timezone)
    {
        if ($this->activities->count() === 0) {
            return null;
        }

        $lastActivity = $this->activities->sortByDesc('date_it_happened')->first();

        return DateHelper::getShortDate(
            DateHelper::createDateFromFormat($lastActivity->date_it_happened, $timezone),
            'en'
        );
    }

    /**
     * Get the last talked to date.
     *
     * @return string
     */
    public function getLastCalled($timezone)
    {
        if (is_null($this->last_talked_to)) {
            return null;
        }

        return DateHelper::createDateFromFormat($this->last_talked_to, $timezone)->diffForHumans();
    }

    /**
     * Get the birthdate of the contact.
     *
     * @return Carbon
     */
    public function getBirthdate()
    {
        if (is_null($this->birthdate)) {
            return null;
        }

        return $this->birthdate;
    }

    /**
     * Gets the age of the contact in years, or returns null if the birthdate
     * is not set.
     *
     * @return int
     */
    public function getAge()
    {
        if (is_null($this->birthdate)) {
            return null;
        }

        return $this->birthdate->diffInYears(Carbon::now());
    }

    /**
     * Get the phone number as a string.
     *
     * @return string or null
     */
    public function getPhone()
    {
        if (is_null($this->phone_number)) {
            return null;
        }

        return $this->phone_number;
    }

    /**
     * Returns 'true' if the birthdate is an approximation
     *
     * @return string
     */
    public function isBirthdateApproximate()
    {
        if ($this->is_birthdate_approximate === 'unknown') {
            return true;
        }

        if ($this->is_birthdate_approximate === 'exact') {
            return false;
        }

        return $this->is_birthdate_approximate;
    }

    /**
     * Get the address in a format like 'Lives in Scranton, MS'.
     *
     * @return string
     */
    public function getPartialAddress()
    {
        $address = $this->getCity();

        if (is_null($address)) {
            return null;
        }

        if (!is_null($this->getProvince())) {
            $address = $address . ', ' . $this->getProvince();
        }

        return $address;
    }

    /**
     * Get the street of the contact.
     * @return string or null
     */
    public function getStreet()
    {
        if (is_null($this->street)) {
            return null;
        }

        return $this->street;
    }

    /**
     * Get the province of the contact.
     * @return string or null
     */
    public function getProvince()
    {
        if (is_null($this->province)) {
            return null;
        }

        return $this->province;
    }

    /**
     * Get the postal code of the contact.
     * @return string or null
     */
    public function getPostalCode()
    {
        if (is_null($this->postal_code)) {
            return null;
        }

        return $this->postal_code;
    }

    /**
     * Get the country of the contact.
     * @return string or null
     */
    public function getCountryName()
    {
        if ($this->country) {
            return $this->country->country;
        }

        return null;
    }

    /**
     * Get the city.
     * @return string
     */
    public function getCity()
    {
        if (is_null($this->city)) {
            return null;
        }

        return $this->city;
    }

    /**
     * Get the countryID of the contact.
     * @return string or null
     */
    public function getCountryID()
    {
        return $this->country_id;
    }

    /**
     * Get the country ISO of the contact.
     * @return string or null
     */
    public function getCountryISO()
    {
        if ($this->country) {
            return $this->country->iso;
        }

        return null;
    }

    /**
     * Get an URL for Google Maps for the address.
     *
     * @return string
     */
    public function getGoogleMapAddress()
    {
        $address = $this->getFullAddress();
        $address = urlencode($address);

        return "https://www.google.ca/maps/place/{$address}";
    }

    /**
     * Get the last updated date.
     *
     * @return string Y-m-d
     */
    public function getLastUpdated()
    {
        return DateHelper::createDateFromFormat($this->updated_at, $this->account->user->timezone)->format('Y/m/d');
    }

    /**
     * Get the total number of reminders.
     *
     * @return int
     */
    public function getNumberOfReminders()
    {
        return $this->reminders->count();
    }

    /**
     * Get the total number of kids.
     *
     * @return int
     */
    public function getNumberOfKids()
    {
        return $this->kids->count();
    }

    /**
     * Get the total number of activities.
     *
     * @return int
     */
    public function getNumberOfActivities()
    {
        return $this->activities->count();
    }

    /**
     * Get the total number of gifts, regardless of ideas or offered.
     *
     * @return int
     */
    public function getNumberOfGifts()
    {
        return $this->gifts->count();
    }

    /**
     * Gets the email address or returns null if undefined.
     *
     * @return string
     */
    public function getEmail()
    {
        if (is_null($this->email)) {
            return null;
        }

        return $this->email;
    }

    /**
     * Gets the Twitter URL or returns null if undefined.
     *
     * @return string
     */
    public function getTwitter()
    {
        if (is_null($this->twitter_profile_url)) {
            return null;
        }

        return $this->twitter_profile_url;
    }

    /**
     * Gets the Facebook URL or returns null if undefined.
     *
     * @return string
     */
    public function getFacebook()
    {
        if (is_null($this->facebook_profile_url)) {
            return null;
        }

        return $this->facebook_profile_url;
    }

    /**
     * Gets the LinkedIn URL or returns null if undefined.
     *
     * @return string
     */
    public function getLinkedin()
    {
        if (is_null($this->linkedin_profile_url)) {
            return null;
        }

        return $this->linkedin_profile_url;
    }

    /**
     * Get the current Significant Other, if it exists, or return null otherwise.
     *
     * @return SignificantOther
     */
    public function getCurrentSignificantOther()
    {
        return $this->significantOther;
    }

    /**
     * Get the notes for this contact. Return an empty collection if no notes.
     *
     * @return Note
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Get the number of notes for this contact.
     *
     * @return int
     */
    public function getNumberOfNotes()
    {
        return $this->notes->count();
    }

    /**
     * Get the kids, if any, as a collection.
     *
     * @return Collection
     */
    public function getKids()
    {
        return $this->kids;
    }

    /**
     * Gets the food preferencies or return null if not defined.
     *
     * @return string
     */
    public function getFoodPreferencies()
    {
        if (is_null($this->food_preferencies)) {
            return null;
        }

        return $this->food_preferencies;
    }

    /**
     * Get the default color of the avatar if no picture is present.
     *
     * @return string
     */
    public function getAvatarColor()
    {
        return $this->default_avatar_color;
    }

    /**
     * Set the default avatar color for this object.
     *
     * @param string|null $color
     * @return void
     */
    public function setAvatarColor($color = null)
    {
        $colors = [
            '#fdb660',
            '#93521e',
            '#bd5067',
            '#b3d5fe',
            '#ff9807',
            '#709512',
            '#5f479a',
            '#e5e5cd',
        ];

        $this->default_avatar_color = $color ?? $colors[mt_rand(0, count($colors) - 1)];

        $this->save();
    }

    /**
     * Log an event in the Event table about this contact.
     *
     * @param  string $objectType Contact, Activity, Kid,...
     * @param  int $objectId ID of the object
     * @param  string $natureOfOperation 'add', 'edit', 'delete'
     * @return int                          Id of the created event
     */
    public function logEvent($objectType, $objectId, $natureOfOperation)
    {
        $event = $this->events()->create([]);
        $event->account_id = $this->account_id;
        $event->object_type = $objectType;
        $event->object_id = $objectId;
        $event->nature_of_operation = $natureOfOperation;
        $event->save();

        return $event->id;
    }

    /**
     * Add a significant other.
     *
     * @param string $firstname
     * @param string $gender
     * @param bool $birthdate_approximate
     * @param string $birthdate
     * @param int $age
     * @param string $timezone
     * @return int
     */
    public function addSignificantOther($firstname, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        $significantOther = $this->significantOthers()->create([]);

        $significantOther->account_id = $this->account_id;
        $significantOther->first_name = ucfirst($firstname);
        $significantOther->gender = $gender;
        $significantOther->is_birthdate_approximate = $birthdate_approximate;
        $significantOther->status = 'active';

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $significantOther->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $significantOther->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $significantOther->birthdate = $birthdate;
        }

        $significantOther->save();

        // Event
        $this->logEvent('significantother', $significantOther->id, 'create');

        return $significantOther->id;
    }

    /**
     * Update the information about the Significant other.
     *
     * @param  SignificantOther|int $significantOther
     * @param  string $firstname
     * @param  string $gender
     * @param  string $birthdate_approximate
     * @param  string $birthdate
     * @param  int $age
     * @param  string $timezone
     * @return int
     */
    public function editSignificantOther($significantOther, $firstname, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        if (!$significantOther instanceof SignificantOther) {
            $significantOther = SignificantOther::findOrFail($significantOther);
        }

        $significantOther->first_name = ucfirst($firstname);
        $significantOther->gender = $gender;
        $significantOther->is_birthdate_approximate = $birthdate_approximate;
        $significantOther->status = 'active';

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $significantOther->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $significantOther->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $significantOther->birthdate = $birthdate;
        }

        $significantOther->save();

        // Event
        $this->logEvent('significantother', $significantOther->id, 'update');

        return $significantOther->id;
    }

    /**
     * Delete the significant other.
     *
     * @param SignificantOther|int $significantOther
     */
    public function deleteSignificantOther($significantOther)
    {
        if (!$significantOther instanceof SignificantOther) {
            $significantOther = SignificantOther::findOrFail($significantOther);
        }

        $significantOther->delete();

        $this->events()
            ->where('object_type', 'significantother')
            ->where('object_id', $significantOther->id)
            ->get()
            ->each
            ->delete();
    }

    /**
     * Update the name of the contact.
     *
     * @param  string $firstName
     * @param  string $middleName
     * @param  string $lastName
     * @return bool
     */
    public function updateName($firstName, $middleName, $lastName)
    {
        if ($firstName == '') {
            return false;
        }

        $this->first_name = $firstName;

        if (!is_null($middleName)) {
            $this->middle_name = $middleName;
        }

        if (!is_null($lastName)) {
            $this->last_name = $lastName;
        }

        $this->save();

        return true;
    }

    /**
     * Update the name of the contact.
     *
     * @param  string $foodPreferencies
     * @return void
     */
    public function updateFoodPreferencies($foodPreferencies)
    {
        if ($foodPreferencies == '') {
            $this->food_preferencies = null;
        } else {
            $this->food_preferencies = $foodPreferencies;
        }

        $this->save();
    }

    /**
     * Add a kid.
     *
     * @param string $name
     * @param string $gender
     * @param bool $birthdate_approximate
     * @param string $birthdate
     * @param int $age
     * @return int the Kid ID
     */
    public function addKid($name, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        $kid = $this->kids()->create([]);
        $kid->account_id = $this->account_id;
        $kid->first_name = ucfirst($name);
        $kid->gender = $gender;
        $kid->is_birthdate_approximate = $birthdate_approximate;

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $kid->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $kid->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $kid->birthdate = $birthdate;
        }

        $kid->save();

        $this->has_kids = 'true';
        $this->number_of_kids = $this->number_of_kids + 1;
        $this->save();

        $this->logEvent('kid', $kid->id, 'create');

        return $kid->id;
    }

    /**
     * Edit a kid.
     *
     * @param Kid|int $kid
     * @param string $name
     * @param string $gender
     * @param bool $birthdate_approximate
     * @param string $birthdate
     * @param int $age
     * @param $timezone
     * @return int the Kid ID
     */
    public function editKid($kid, $name, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        if (!$kid instanceof Kid) {
            $kid = Kid::findOrFail($kid);
        }

        $kid->first_name = ucfirst($name);
        $kid->gender = $gender;
        $kid->is_birthdate_approximate = $birthdate_approximate;

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $kid->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $kid->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $kid->birthdate = $birthdate;
        }

        $kid->save();

        $this->logEvent('kid', $kid->id, 'update');

        return $kid->id;
    }

    /**
     * Delete the kid.
     *
     * @param Kid|int $kid
     */
    public function deleteKid($kid)
    {
        if (!$kid instanceof Kid) {
            $kid = Kid::findOrFail($kid);
        }

        $kid->delete();

        // Delete all events
        $this->events()
            ->where('object_type', 'kid')
            ->where('object_id', $kid->id)
            ->get()
            ->each
            ->delete();

        // Decrease number of kids
        $this->number_of_kids = $this->number_of_kids - 1;

        if ($this->number_of_kids < 1) {
            $this->number_of_kids = 0;
            $this->has_kids = 'false';
        }

        $this->save();
    }

    /**
     * Create a note.
     *
     * @param string $body
     * @return mixed
     */
    public function addNote($body)
    {
        $note = $this->notes()->create([]);
        $note->account_id = $this->account_id;
        $note->body = $body;
        $note->save();

        $this->number_of_notes = $this->number_of_notes + 1;
        $this->save();

        $this->logEvent('note', $note->id, 'create');

        return $note->id;
    }

    /**
     * Delete the note.
     *
     * @param Note|int $note
     */
    public function deleteNote($note)
    {
        if (!$note instanceof Note) {
            $note = Note::findOrFail($note);
        }

        $note->delete();

        // Decrease number of notes
        $this->number_of_notes = $this->number_of_notes - 1;

        if ($this->number_of_notes < 1) {
            $this->number_of_notes = 0;
        }

        $this->save();

        // Delete all events
        $this->events()
            ->where('object_type', 'note')
            ->where('object_id', $note->id)
            ->get()
            ->each
            ->delete();
    }

    /**
     * Get all the activities, if any.
     *
     * @return Collection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Refresh statistics about activities
     * TODO: unit test.
     *
     * @return void
     */
    public function calculateActivitiesStatistics()
    {
        // Delete the Activities statistics table for this contact
        $this->activityStatistics->each->delete();

        // Create the statistics again
        $this->activities->groupBy('date_it_happened.year')
            ->map(function (Collection $activities, $year) {
                $activityStatistic = $this->activityStatistics()->create([]);
                $activityStatistic->account_id = $this->account_id;
                $activityStatistic->year = $year;
                $activityStatistic->count = $activities->count();;
                $activityStatistic->save();
            });
    }

    /**
     * Get statistics for the contact
     * TODO: add unit test.
     */
    public function getActivitiesStats()
    {
        return $this->activityStatistics;
    }

    /**
     * Get all the reminders, if any.
     *
     * @return Collection
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * Get all the gifts, if any.
     *
     * @return Collection
     */
    public function getGifts()
    {
        return $this->gifts;
    }

    /**
     * Get all the gifts offered, if any.
     */
    public function getGiftsOffered()
    {
        return $this->gifts()->offered()->get();
    }

    /**
     * Get all the gift ideas, if any.
     */
    public function getGiftIdeas()
    {
        return $this->gifts()->isIdea()->get();
    }

    /**
     * Get all the tasks in the in progress state, if any.
     */
    public function getTasksInProgress()
    {
        return $this->tasks()->inProgress()->get();
    }

    /**
     * Get all the tasks no matter the state, if any.
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Get all the tasks in the in completed state, if any.
     */
    public function getCompletedTasks()
    {
        return $this->tasks()->completed()->get();
    }

    /**
     * Returns the URL of the avatar with the given size
     * @param  int $size
     * @return string
     */
    public function getAvatarURL($size)
    {
        $original_avatar_url = Storage::disk('public')->url($this->avatar_file_name);
        $avatar_filename = pathinfo($original_avatar_url, PATHINFO_FILENAME);
        $avatar_extension = pathinfo($original_avatar_url, PATHINFO_EXTENSION);
        $resized_avatar = 'avatars/' . $avatar_filename . '_' . $size . '.' . $avatar_extension;

        return Storage::disk('public')->url($resized_avatar);
    }

    public function getGravatar($size)
    {
        if (empty($this->email)) {
            return false;
        }
        $gravatar_url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->email)));
        // check if gravatar exists by appending ?d=404, returns 404 response if does not exist
        $gravatarHeaders = get_headers($gravatar_url . "?d=404");
        if ($gravatarHeaders[0] == "HTTP/1.1 404 Not Found") {
            return false;
        }

        return $gravatar_url . "?s=" . $size;
    }

    /**
     * Check if the contact has debt (by the contact or the user for this contact)
     * @return boolean
     */
    public function hasDebt()
    {
        return $this->debts()->count() !== 0;
    }

    /**
     * Get all the tasks no matter the state, if any.
     */
    public function getDebts()
    {
        return $this->debts;
    }

}
