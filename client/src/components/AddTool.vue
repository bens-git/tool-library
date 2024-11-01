<template>
    <v-container>
      <v-form @submit.prevent="addTool">
        <v-text-field
          v-model="name"
          label="Tool Name"
          required
        ></v-text-field>
        <v-text-field
          v-model="location"
          label="Location"
          required
        ></v-text-field>
        <v-textarea
          v-model="description"
          label="Description"
        ></v-textarea>
        <v-btn type="submit" color="primary">Add Tool</v-btn>
      </v-form>
    </v-container>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    data() {
      return {
        name: '',
        location: '',
        description: ''
      };
    },
    methods: {
      addTool() {
        axios.post('/api/tools', {
          name: this.name,
          location: this.location,
          description: this.description
        })
        .then(response => {
          console.log('Tool added successfully', response);
          // handle successful addition (e.g., redirect, show message, etc.)
        })
        .catch(error => {
          console.error('There was an error adding the tool:', error);
        });
      }
    }
  };
  </script>